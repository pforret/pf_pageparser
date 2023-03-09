<?php

namespace Pforret\PfPageparser\Tests;

use Pforret\PfPageparser\PfPageparser;
use PHPUnit\Framework\TestCase;

class InstagramTest extends TestCase
{
    public function testIgSharedData()
    {
        $url = 'https://www.instagram.com/jenniferaniston/';
        $pp = new PfPageparser();

        $pp->load_from_url($url);
        self::assertTrue(str_contains($pp->get_content(), 'window._sharedData'), '_sharedData not found in Instagram HTML');
    }

    public function testIgTrim()
    {
        $url = 'https://www.instagram.com/jenniferaniston/';
        $pp = new PfPageparser();

        $pp->load_from_url($url);
        $pp->trim('window._sharedData', '<script type="text/javascript"');
        self::assertNotEmpty($pp->get_content(), 'HTML retrieved and trimmed');
    }

    public function testIgChunks()
    {
        $url = 'https://www.instagram.com/jenniferaniston/';
        $pp = new PfPageparser();

        $pp->load_from_url($url);
        $pp->trim('window._sharedData', '<script type="text/javascript"');

        $pp->parse_fom_chunks('/"edge_followed_by":{"count":(\d+)}/', true);
        self::assertGreaterThan(0, count($pp->results()), 'Found follower count');

        $pp->parse_fom_chunks('/"display_url":"([^"]*)"/', true, true);
        self::assertGreaterThan(0, count($pp->results()), 'Found image list');
    }
}
