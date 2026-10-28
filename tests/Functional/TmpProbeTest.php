<?php
namespace App\Tests\Functional;
class TmpProbeTest extends AppWebTestCase {
    public function testMatrix(): void {
        $c = static::getContainer()->get('doctrine')->getConnection();
        $t = fn($tbl) => $c->fetchOne("SELECT teacher_id FROM $tbl LIMIT 1");
        $reqs = [
          'wptVisitReport' => '/fct/visita/'.$t('wpt_contact').'/descargar', 'wptExpReport' => '/fct/desplazamiento/'.$t('wpt_travel_expense').'/descargar',
          'wltExpReport' => '/dual/desplazamiento/'.$t('wlt_travel_expense').'/descargar', 'itpExpReport' => '/formacion/desplazamiento/'.$t('itp_travel_expense').'/descargar',
        ];
        foreach (['alfa.tutor','alfa.profesor','alfa.jefe','alfa.admin','alfa.coordinador','alfa.gestorffe','alfa.economico','alfa.tutorlaboral','alfa.alumno1','beta.admin'] as $u) {
            $this->login($this->getUser($u), true, str_starts_with($u,'beta')?'23000002':'23000001'); $line = $u.':';
            foreach ($reqs as $n => $p) { $this->client->request('GET', $p); $r=$this->client->getResponse(); $line .= ' '.$n.'='.$r->getStatusCode().(str_contains((string)$r->headers->get('Content-Type'),'pdf')?'P':''); }
            file_put_contents(getenv('PROBE_OUT'), $line."\n", FILE_APPEND);
        }
        self::assertTrue(true);
    }
}
