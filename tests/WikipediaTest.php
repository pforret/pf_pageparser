<?php

namespace Pforret\PfPageparser\Tests;

use Pforret\PfPageparser\PfPageparser;
use PHPUnit\Framework\TestCase;

class WikipediaTest extends TestCase
{
    public function test_wikipedia()
    {
        $url = '';
        $pp = new PfPageparser();

        $pp->load_from_url('https://www.wikipedia.org/')
            ->trim('<div class="other-projects">', '<p class="site-license">');
        $this->assertNotEmpty($pp->get_content(), 'HTML retrieved and trimmed');

        $pp->load_from_url('https://www.wikipedia.org/')
            ->trim('<div class="other-projects">', '<p class="site-license">')
            ->split_chunks('<div class="other-project">')
            ->filter_chunks(['other-project-title']);
        $this->assertTrue(count($pp->get_chunks()) > 0, 'HTML split and filtered');

        $pp->load_from_url('https://www.wikipedia.org/')
            ->trim('<div class="other-projects">', '<p class="site-license">')
            ->split_chunks('<div class="other-project">')
            ->filter_chunks(['other-project-title'])
            ->parse_fom_chunks('|<span class="other-project-title jsl10n" data-jsl10n=".*">([\w\s]*)</span>|', true);
        $this->assertTrue(count($pp->results()) > 0, 'HTML split and filtered');
        $this->assertTrue(in_array('Wiktionary', $pp->results()), 'Parsed from chunks');
    }
}
