<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Regenerate the Private Key and Certificate files
 *
 * @package    auth_saml2
 * @copyright  Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('setup.php');
require('regenerate_form.php');

require_login();
require_capability('moodle/site:config', context_system::instance());
$PAGE->set_url("$CFG->httpswwwroot/auth/saml2/regenerate.php");
$PAGE->set_course($SITE);

$mform = new regenerate_form();

if ($fromform = $mform->get_data()) {
    $dn = array( 'countryName' => $fromform->countryname,
                        'stateOrProvinceName' => $fromform->stateorprovincename,
                        'localityName' => $fromform->localityname,
                        'organizationName' => $fromform->organizationname,
                        'organizationalUnitName' => $fromform->organizationalunitname,
                        'commonName' => $fromform->commonname,
                        'emailAddress' => $fromform->email
                    );
    $numberofdays = $fromform->expirydays;

    $saml2auth = new auth_plugin_saml2();
    create_certificates($saml2auth, $dn, $numberofdays);

}
    $path = $saml2auth->certdir . $saml2auth->spname . '.crt';
    $data = openssl_x509_parse(file_get_contents($path));

    echo $OUTPUT->header();
    //TODO: generate all this the Moodle way
    echo "<h1>Regenerate Private Key and Certificate</h1>";
    echo "<p>Path: $path</p>";
    echo "<h3>Warning: Generating a new certificate will overwrite the current one and you may need to update your IDP.</h3>";

    //Set default data
    $date1 = date("Y-m-d\TH:i:s\Z",str_replace ('Z', '', $data['validFrom_time_t']));
    $date2 =date("Y-m-d\TH:i:s\Z",str_replace ('Z', '', $data['validTo_time_t']));
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    $expirydays = $interval->format('%a');

    $toform = array ("countryname"=>$data['subject']['C'],
                    "stateorprovincename"=>$data['subject']['ST'],
                    "localityname"=>$data['subject']['L'],
                    "organizationname"=>$data['subject']['O'],
                    "organizationalunitname"=>$data['subject']['OU'],
                    "commonname"=>$data['subject']['CN'],
                    "email"=>$data['subject']['emailAddress'],
                    "expirydays"=>$expirydays
    );
    $mform->set_data($toform); // Load current data into form
    $mform->display(); // Displays the form

    echo $OUTPUT->footer();

