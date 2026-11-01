# Hallazgos de seguridad durante el rediseño (rama `ux-redesign`)

Registro de los fallos de seguridad encontrados al migrar los módulos, para poder aplicarlos con `git cherry-pick` en otras
ramas que hayan divergido. Cada entrada indica si afecta solo a código nuevo de esta rama o también a código anterior.

## 1. Informe de contactos de un docente de otro centro (Live Component) — solo `ux-redesign`

- **Dónde:** `src/Twig/Components/Wlt/ContactEducationalTutorReportForm.php` y `src/Service/WltModule/ContactReportTeacherProvider.php`
  (commit `e99c0bce`).
- **Fallo:** el componente comprobaba que el docente estuviera entre los que la persona puede consultar, pero el cálculo de
  esos docentes no verificaba que el curso académico del docente perteneciera al centro actual. Un administrador de otro
  centro, repitiendo la petición firmada del componente (o pidiéndola con un docente ajeno), obtenía el formulario del informe
  de un docente de otro centro.
- **Corrección:** `ContactReportTeacherProvider::forAcademicYear()` devuelve una lista vacía si
  `$academicYear->getOrganization()` no es el centro actual.
- **Pruebas:** `WltContactFormComponentTest::testEducationalTutorReportFormRecalculatesTheWorkcenterWhenProjectsChangeAndIsDeniedToAnotherOrganization`.
- **Otras ramas:** el controlador anterior (`ContactController::educationTutorReportForm`, ramas `v7`, `symfony-7.4`, `symfony-8.1`)
  **no** estaba afectado: ya devolvía 403 (lo cubre `WltContactReportTest::testAdministratorOfAnotherCentreCannotGetThem`). No hay
  nada que portar; solo si se llevase el componente a otra rama habría que llevar también esta comprobación.

## Regla para los Live Components

Cada actualización llega a `/_components/...` como una petición nueva, y las propiedades firmadas (`LiveProp` con entidades) no
están ligadas a la sesión. Por eso `authorize()` / `#[PostHydrate]` debe repetir **todas** las comprobaciones del controlador,
incluida la pertenencia de cada entidad recibida (curso, docente, proyecto…) al centro actual. Cada componente nuevo lleva una
prueba que repite la petición como administrador de otro centro y espera 403 (`*ListComponentsTest`, `*FormComponentTest`).

Además, la prueba de intrusión `CrossOrganization*Test` (grupo `slow`) se ejecutó con todo el módulo de FP dual migrado hasta
reuniones y contactos, sin más fugas.
