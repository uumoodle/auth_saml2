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

namespace auth_saml2;

use auth_saml2\task\metadata_refresh;

/**
 * Testcase class for metadata_refresh task class.
 *
 * @package    auth_saml2
 * @author     Sam Chaffee
 * @copyright  Copyright (c) 2017 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class metadata_refresh_test extends \advanced_testcase {

    /** @var \Prophecy\Prophet */
    protected $prophet;

    /**
     * Set up
     */
    public function setUp(): void {
        if (class_exists('\\Prophecy\\Prophet')) {
            $this->prophet = new \Prophecy\Prophet();
        }
        $this->resetAfterTest(true);
    }

    /**
     * Tear down after every test.
     */
    protected function tearDown(): void {
        $this->prophet = null;  // Required for Totara 12+ support (see issue #578).
    }

    public function test_metadata_refresh_disabled(): void {
        set_config('idpmetadatarefresh', 0, 'auth_saml2');
        set_config('idpmetadata', 'http://somefakeidpurl.local', 'auth_saml2');

        $refreshtask = new metadata_refresh();
        $this->expectOutputString('IdP metadata refresh is not configured. '.
            "Enable it in the auth settings or disable this scheduled task\n");
        self::assertFalse($refreshtask->execute());
    }

    public function test_metadata_refresh_idpmetadata_non_url(): void {
        $randomxml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<somexml>yada</somexml>
XML;
        set_config('idpmetadatarefresh', 1, 'auth_saml2');
        set_config('idpmetadata', $randomxml, 'auth_saml2');

        $refreshtask = new metadata_refresh();

        $this->expectOutputString('IdP metadata config not a URL, nothing to refresh.' . "\n");
        $refreshtask->execute();
    }

    public function test_metadata_refresh_idpmetadata_notconfigured(): void {
        set_config('idpmetadatarefresh', 1, 'auth_saml2');
        set_config('idpmetadata', null, 'auth_saml2');

        $refreshtask = new metadata_refresh();

        $this->expectOutputString('IdP metadata not configured.' . "\n");
        self::assertFalse($refreshtask->execute());
    }

    public function test_metadata_refresh_fetch_fails(): void {
        $this->markTestSkipped('This test needs to be fixed or removed.');

        if (!isset($this->prophet)) {
            $this->markTestSkipped('Skipping due to Prophecy library not available');
        }

        set_config('idpmetadatarefresh', 1, 'auth_saml2');
        set_config('idpmetadata', 'http://somefakeidpurl.local', 'auth_saml2');
        $fetcher = $this->prophet->prophesize('auth_saml2\metadata_fetcher');

        $refreshtask = new metadata_refresh();
        $refreshtask->set_fetcher($fetcher->reveal());

        $fetcher->fetch('http://somefakeidpurl.local')->willThrow(new \moodle_exception('metadatafetchfailed', 'auth_saml2'));
        $refreshtask->execute();
    }

    public function test_metadata_refresh_parse_fails(): void {
        $this->markTestSkipped('This test needs to be fixed or removed.');

        if (!isset($this->prophet)) {
            $this->markTestSkipped('Skipping due to Prophecy library not available');
        }

        set_config('idpmetadatarefresh', 1, 'auth_saml2');
        set_config('idpmetadata', 'http://somefakeidpurl.local', 'auth_saml2');
        $fetcher = $this->prophet->prophesize('auth_saml2\metadata_fetcher');
        $parser = $this->prophet->prophesize('auth_saml2\metadata_parser');

        $refreshtask = new metadata_refresh();
        $refreshtask->set_fetcher($fetcher->reveal());
        $refreshtask->set_parser($parser->reveal());

        $fetcher->fetch('http://somefakeidpurl.local')->willReturn('doesnotmatter');
        $parser->parse('doesnotmatter')->willThrow(new \moodle_exception('errorparsingxml', 'auth_saml2', '', 'error'));
        $refreshtask->execute();
    }

    public function test_metadata_refresh_parse_no_entityid(): void {
        $this->markTestSkipped('This test needs to be fixed or removed.');
    }

    public function test_metadata_refresh_parse_no_idpname(): void {
        $this->markTestSkipped('This test needs to be fixed or removed.');
    }

    public function test_metadata_refresh_write_fails(): void {
        $this->markTestSkipped('This test needs to be fixed or removed.');

        if (!isset($this->prophet)) {
            $this->markTestSkipped('Skipping due to Prophecy library not available');
        }

        $this->setExpectedExceptionFromAnnotation();

        set_config('idpmetadatarefresh', 1, 'auth_saml2');
        set_config('idpmetadata', 'http://somefakeidpurl.local', 'auth_saml2');

        $fetcher = $this->prophet->prophesize('auth_saml2\metadata_fetcher');
        $parser = $this->prophet->prophesize('auth_saml2\metadata_parser');
        $writer = $this->prophet->prophesize('auth_saml2\metadata_writer');

        $refreshtask = new metadata_refresh();
        $refreshtask->set_fetcher($fetcher->reveal());
        $refreshtask->set_parser($parser->reveal());
        $refreshtask->set_writer($writer->reveal());

        $fetcher->fetch('http://somefakeidpurl.local')->willReturn('somexml');
        $parser->parse('somexml')->willReturn(null);
        $parser->get_entityid()->willReturn('Some id');
        $parser->get_idpdefaultname()->willReturn('Default name');
        $md5 = md5('Some id');
        $writer->write($md5 . '.idp.xml', 'somexml')->willThrow(new coding_exception('Metadata write failed: some error'));
        $refreshtask->execute();
    }
}
