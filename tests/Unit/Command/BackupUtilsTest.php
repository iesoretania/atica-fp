<?php

namespace App\Tests\Unit\Command;

use App\Command\BackupUtils;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * La contraseña de la base de datos no debe aparecer en la línea de comandos de mysqldump y mysql (la ve
 * cualquier usuario del sistema en la lista de procesos) ni en los mensajes que se muestran al usuario.
 * Se sustituyen los programas por scripts que registran cómo se les ha llamado.
 */
class BackupUtilsTest extends TestCase
{
    private const PASSWORD = 'cl@ve "con" \\ comillas $y espacios';

    private string $directory;
    private string $originalPath;
    private BufferedOutput $output;
    private SymfonyStyle $io;
    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/atica-backup-test-' . bin2hex(random_bytes(4));
        mkdir($this->directory . '/bin', 0755, true);
        $this->originalPath = (string) getenv('PATH');

        // cada script guarda sus argumentos, el contenido y los permisos del fichero de opciones, y puede fallar
        $script = <<<'SH'
#!/bin/sh
printf '%s\n' "$@" > "$STUB_DIR/args"
for argument in "$@"; do
    case "$argument" in
        --defaults-extra-file=*)
            file="${argument#--defaults-extra-file=}"
            cat "$file" > "$STUB_DIR/options"
            ls -l "$file" | cut -c1-10 > "$STUB_DIR/permissions"
            echo "$file" > "$STUB_DIR/options-path"
            ;;
    esac
done
if [ -n "$STUB_FAIL" ]; then
    echo "fallo simulado" >&2
    exit 1
fi
exit 0
SH;
        foreach (['mysqldump', 'mysql'] as $program) {
            file_put_contents($this->directory . "/bin/$program", $script);
            chmod($this->directory . "/bin/$program", 0755);
        }
        $this->setEnvironment('PATH', $this->directory . '/bin:' . $this->originalPath);
        $this->setEnvironment('STUB_DIR', $this->directory);
        $this->setEnvironment('STUB_FAIL', null);

        $this->output = new BufferedOutput();
        $this->io = new SymfonyStyle(new ArrayInput([]), $this->output);
        $this->translator = new class implements TranslatorInterface {
            public function trans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
            {
                return $id . ' ' . json_encode($parameters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            public function getLocale(): string
            {
                return 'es';
            }
        };
    }

    /**
     * Process solo hereda del entorno las variables que también están en $_SERVER
     */
    private function setEnvironment(string $name, ?string $value): void
    {
        if ($value === null) {
            putenv($name);
            unset($_SERVER[$name]);
            return;
        }
        putenv($name . '=' . $value);
        $_SERVER[$name] = $value;
    }

    protected function tearDown(): void
    {
        $this->setEnvironment('PATH', $this->originalPath);
        $this->setEnvironment('STUB_DIR', null);
        $this->setEnvironment('STUB_FAIL', null);
        foreach (glob($this->directory . '/{*,bin/*}', GLOB_BRACE) ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->directory . '/bin');
        @rmdir($this->directory);
    }

    private function recorded(string $name): string
    {
        return (string) @file_get_contents($this->directory . '/' . $name);
    }

    private function backup(string $password): bool
    {
        return BackupUtils::createDatabaseBackup(
            $this->io,
            $this->translator,
            'basededatos',
            'usuario',
            $password,
            '127.0.0.1',
            '3306',
            $this->directory . '/copia.sql'
        );
    }

    private function restore(string $password): bool
    {
        return BackupUtils::restoreDatabaseBackup(
            $this->io,
            $this->translator,
            'basededatos',
            'usuario',
            $password,
            '127.0.0.1',
            '3306',
            $this->directory . '/copia.sql'
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function operationProvider(): iterable
    {
        yield 'copia de seguridad' => ['backup'];
        yield 'restauración' => ['restore'];
    }

    /**
     * @dataProvider operationProvider
     */
    public function testPasswordIsNotInTheCommandLineNorInTheMessages(string $operation): void
    {
        self::assertTrue($this->$operation(self::PASSWORD));

        $arguments = $this->recorded('args');
        self::assertStringNotContainsString('cl@ve', $arguments, 'La contraseña aparece en la línea de comandos');
        foreach (explode("\n", trim($arguments)) as $argument) {
            self::assertStringStartsNotWith('-p', $argument, 'No debe pasarse la contraseña con -p');
        }
        self::assertStringNotContainsString('cl@ve', $this->output->fetch(), 'La contraseña aparece en los mensajes');

        // el fichero de opciones es el primer argumento (lo exige MySQL) y contiene la contraseña bien escapada
        self::assertStringStartsWith('--defaults-extra-file=', explode("\n", $arguments)[0]);
        self::assertSame("[client]\npassword=\"cl@ve \\\"con\\\" \\\\ comillas \$y espacios\"\n", $this->recorded('options'));
    }

    /**
     * @dataProvider operationProvider
     */
    public function testCredentialsFileIsPrivateAndRemovedAfterwards(string $operation): void
    {
        self::assertTrue($this->$operation(self::PASSWORD));

        self::assertSame("-rw-------\n", $this->recorded('permissions'), 'El fichero con la contraseña debe ser privado');
        self::assertFileDoesNotExist(trim($this->recorded('options-path')));
    }

    /**
     * @dataProvider operationProvider
     */
    public function testEmptyPasswordNeverMakesTheClientAskForIt(string $operation): void
    {
        self::assertTrue($this->$operation(''));

        foreach (explode("\n", trim($this->recorded('args'))) as $argument) {
            // "-p" sin valor hace que el cliente pida la contraseña por teclado y el proceso se quede esperando
            self::assertStringStartsNotWith('-p', $argument);
        }
        self::assertSame("[client]\npassword=\"\"\n", $this->recorded('options'));
    }

    /**
     * @dataProvider operationProvider
     */
    public function testFailureDoesNotLeakThePasswordAndRemovesTheFile(string $operation): void
    {
        $this->setEnvironment('STUB_FAIL', '1');

        self::assertFalse($this->$operation(self::PASSWORD));

        $messages = $this->output->fetch();
        self::assertStringContainsString('message.run_error', $messages);
        self::assertStringContainsString('fallo simulado', $messages);
        self::assertStringNotContainsString('cl@ve', $messages, 'El mensaje de error revela la contraseña');
        self::assertFileDoesNotExist(trim($this->recorded('options-path')));
    }
}
