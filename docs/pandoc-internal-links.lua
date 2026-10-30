-- Pandoc Lua filter para el PDF del manual.
--
-- El manual se redacta como varios .md que MkDocs publica como páginas
-- independientes, pero el PDF los concatena en un único documento. Este filtro
-- reconcilia ambos mundos:
--
--   1. Asigna a cada encabezado el mismo identificador que le da MkDocs
--      (algoritmo de "slug" sin acentos), respetando los identificadores
--      explícitos (`## Título {#mi-ancla}`).
--   2. Convierte los enlaces entre ficheros (`fichero.md`, `fichero.md#ancla`)
--      en enlaces internos del PDF, buscando el destino en una tabla construida
--      con los encabezados reales. Así funcionan aunque el nombre del fichero no
--      coincida con su título, aunque el encabezado empiece por un número (el id
--      del PDF lleva un prefijo "sec-") o tenga un id explícito.
--
-- REQUISITO: pandoc debe ejecutarse con `-f markdown-auto_identifiers`, para que
-- los encabezados lleguen sin identificador salvo los explícitos. Sin esa
-- extensión desactivada no se puede distinguir un id explícito de uno automático.
-- Los ficheros de entrada se leen con PANDOC_STATE.input_files para conocer el
-- título (H1) de cada uno.

-- Diacríticos del español -> ASCII (NFKD + ascii ignore, como hace MkDocs).
local accents = {
  ["á"] = "a", ["é"] = "e", ["í"] = "i", ["ó"] = "o", ["ú"] = "u",
  ["ü"] = "u", ["ñ"] = "n",
  ["Á"] = "A", ["É"] = "E", ["Í"] = "I", ["Ó"] = "O", ["Ú"] = "U",
  ["Ü"] = "U", ["Ñ"] = "N",
}

local function strip_accents(s)
  return (s:gsub("[%z\1-\127\194-\244][\128-\191]*", function(c)
    return accents[c] or c
  end))
end

-- Replica mkdocs/Python-Markdown slugify: NFKD->ascii, quita todo lo que no sea
-- alfanumérico/espacio/guion, minúsculas, y colapsa espacios y guiones en "-".
local function slugify(text)
  text = strip_accents(text)
  text = text:gsub("[\128-\255]", "")  -- restos no ASCII (p. ej. «—»), que quedarían como bytes inválidos
  text = text:gsub("[^%w%s%-]", "")
  text = text:lower()
  text = text:gsub("[%s%-]+", "-")
  text = text:gsub("^%-+", ""):gsub("%-+$", "")
  return text
end

-- Un id que empieza por dígito no es un selector CSS válido y rompe el
-- target-counter de Paged.js (números de página del índice). Se antepone un
-- prefijo; los enlaces lo reciben a través de la tabla de ids, no a mano.
local function css_safe(id)
  if id:match("^%d") then
    return "sec-" .. id
  end
  return id
end

-- Dedup estilo MkDocs: id, id_1, id_2, ...
local seen = {}
local function unique(id)
  local candidate = id
  local n = 0
  while seen[candidate] or candidate == "" do
    n = n + 1
    candidate = id .. "_" .. n
  end
  seen[candidate] = true
  return candidate
end

local function basename(path)
  return path:match("([^/\\]+)$") or path
end

-- Título (primer "# ") de un fichero Markdown, ignorando los bloques de código.
local function read_title(path)
  local f = io.open(path, "r")
  if not f then
    return nil
  end
  local in_fence = false
  for line in f:lines() do
    if line:match("^%s*```") or line:match("^%s*~~~") then
      in_fence = not in_fence
    elseif not in_fence then
      local title = line:match("^#%s+(.-)%s*$")
      if title then
        f:close()
        return (title:gsub("%s*{[^}]*}%s*$", ""))
      end
    end
  end
  f:close()
  return nil
end

function Pandoc(doc)
  -- slug del H1 -> nombre del fichero que lo contiene
  local file_of_title = {}
  for _, path in ipairs(PANDOC_STATE.input_files or {}) do
    local title = read_title(path)
    if title then
      file_of_title[slugify(title)] = basename(path)
    end
  end

  local by_file = {}    -- fichero -> { clave de ancla -> id final }
  local title_id = {}   -- fichero -> id final de su H1
  local global = {}     -- clave de ancla -> id final (primera aparición)
  local current = nil

  local function register(map, key, id)
    if key ~= "" and map[key] == nil then
      map[key] = id
    end
  end

  -- Recorre todo el documento (compatible con pandoc 2.x, donde Pandoc:walk no existe).
  local function walk(filter)
    doc.blocks = pandoc.walk_block(pandoc.Div(doc.blocks), filter).content
  end

  -- Pasada 1: identificadores definitivos de los encabezados.
  walk({
    Header = function(el)
      local base = slugify(pandoc.utils.stringify(el.content))
      local explicit = el.identifier ~= ""
      local key = explicit and el.identifier or base

      if el.level == 1 then
        current = file_of_title[base] or current
      end

      local id = unique(css_safe(key))
      el.identifier = id

      if current ~= nil then
        by_file[current] = by_file[current] or {}
        register(by_file[current], key, id)
        -- Un id explícito también se alcanza por el slug del texto.
        register(by_file[current], base, id)
        if el.level == 1 then
          title_id[current] = id
        end
      end
      register(global, key, id)
      register(global, base, id)
      return el
    end,
  })

  -- Pasada 2: enlaces internos.
  walk({
    Link = function(el)
      local target = el.target
      -- No tocar enlaces externos.
      if target:match("^%a[%w+.-]*://") then
        return el
      end

      if target:sub(1, 1) == "#" then
        local frag = target:sub(2)
        el.target = "#" .. (global[frag] or frag)
        return el
      end

      local file, frag = target:match("^([%w%-_]+)%.md#(.+)$")
      if file then
        local name = file .. ".md"
        local id = (by_file[name] and by_file[name][frag]) or global[frag] or frag
        el.target = "#" .. id
        return el
      end

      file = target:match("^([%w%-_]+)%.md$")
      if file then
        local name = file .. ".md"
        if title_id[name] then
          el.target = "#" .. title_id[name]
        else
          -- Sin el fichero a la vista: se deduce del nombre ("NN-" y ".md" fuera).
          local fallback = file:gsub("^%d+%-", "")
          el.target = "#" .. (global[fallback] or fallback)
        end
        return el
      end

      return el
    end,
  })

  return doc
end
