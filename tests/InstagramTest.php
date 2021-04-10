<?php

namespace Pforret\PfPageparser\Tests;

use Pforret\PfPageparser\PfPageparser;
use PHPUnit\Framework\TestCase;


class InstagramTest extends TestCase
{

    public function test_rachel()
    {
        $url = "https://www.instagram.com/jenniferaniston/";
        $pp = new PfPageparser();

        $pp->load_from_url($url);
        self::assertTrue(strpos($pp->get_content(), 'window._sharedData', 0) > 0, "Data found in Instagram HTML");

        $pp->trim('window._sharedData', '<script type="text/javascript"');
        self::assertNotEmpty($pp->get_content(), 'HTML retrieved and trimmed');

        $pp->parse_fom_chunks('/"edge_followed_by":{"count":(\d+)}/', true);
        self::assertGreaterThan(0, count($pp->results()), "Found follower count");

        $pp->parse_fom_chunks('/"display_url":"([^"]*)"/', true, true);
        self::assertGreaterThan(0, count($pp->results()), "Found image list");

    }

}
