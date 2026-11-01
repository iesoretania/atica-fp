<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/js/app.js',
        'entrypoint' => true,
    ],
    'select2entity' => [
        'path' => './assets/js/select2entity.js',
        'entrypoint' => true,
    ],
    'list' => [
        'path' => './assets/js/list.js',
        'entrypoint' => true,
    ],
    'person' => [
        'path' => './assets/js/person.js',
        'entrypoint' => true,
    ],
    'edu_travel_route' => [
        'path' => './assets/js/edu/travel_route.js',
        'entrypoint' => true,
    ],
    'wlt_agreement' => [
        'path' => './assets/js/wlt/agreement.js',
        'entrypoint' => true,
    ],
    'wlt_contact' => [
        'path' => './assets/js/wlt/contact.js',
        'entrypoint' => true,
    ],
    'wlt_contact_educational_tutor_report' => [
        'path' => './assets/js/wlt/contact_educational_tutor_report.js',
        'entrypoint' => true,
    ],
    'wlt_meeting' => [
        'path' => './assets/js/wlt/meeting.js',
        'entrypoint' => true,
    ],
    'wpt_agreement' => [
        'path' => './assets/js/wpt/agreement.js',
        'entrypoint' => true,
    ],
    'wpt_agreement_enrollment' => [
        'path' => './assets/js/wpt/agreement_enrollment.js',
        'entrypoint' => true,
    ],
    'wpt_visit' => [
        'path' => './assets/js/wpt/visit.js',
        'entrypoint' => true,
    ],
    'itp_activity' => [
        'path' => './assets/js/itp/activity.js',
        'entrypoint' => true,
    ],
    'itp_company' => [
        'path' => './assets/js/itp/company.js',
        'entrypoint' => true,
    ],
    'itp_group' => [
        'path' => './assets/js/itp/group.js',
        'entrypoint' => true,
    ],
    'itp_workcenter' => [
        'path' => './assets/js/itp/workcenter.js',
        'entrypoint' => true,
    ],
    'itp_workcenter_batch' => [
        'path' => './assets/js/itp/workcenter_batch.js',
        'entrypoint' => true,
    ],
    'itp_contact' => [
        'path' => './assets/js/itp/contact.js',
        'entrypoint' => true,
    ],
    'jquery' => [
        'version' => '3.7.1',
    ],
    'bootstrap' => [
        'version' => '4.6.2',
    ],
    'popper.js' => [
        'version' => '1.16.1',
    ],
    'select2' => [
        'version' => '4.0.13',
    ],
    'lodash.debounce' => [
        'version' => '4.0.8',
    ],
    'quill' => [
        'version' => '2.0.3',
    ],
    'lodash-es' => [
        'version' => '4.17.21',
    ],
    'parchment' => [
        'version' => '3.0.0',
    ],
    'quill-delta' => [
        'version' => '5.1.0',
    ],
    'eventemitter3' => [
        'version' => '5.0.1',
    ],
    'select2/dist/css/select2.min.css' => [
        'version' => '4.0.13',
        'type' => 'css',
    ],
    'fast-diff' => [
        'version' => '1.3.0',
    ],
    'lodash.clonedeep' => [
        'version' => '4.5.0',
    ],
    'lodash.isequal' => [
        'version' => '4.5.0',
    ],
    'bootswatch/dist/yeti/bootstrap.min.css' => [
        'version' => '4.6.2',
        'type' => 'css',
    ],
    'select2-theme-bootstrap4/dist/select2-bootstrap.min.css' => [
        'version' => '1.0.2',
        'type' => 'css',
    ],
    '@fortawesome/fontawesome-free/css/fontawesome.min.css' => [
        'version' => '6.7.2',
        'type' => 'css',
    ],
    '@fortawesome/fontawesome-free/css/solid.min.css' => [
        'version' => '6.7.2',
        'type' => 'css',
    ],
    'select2/dist/js/i18n/es' => [
        'version' => '4.0.13',
    ],
    'quill/dist/quill.snow.css' => [
        'version' => '2.0.3',
        'type' => 'css',
    ],
];
