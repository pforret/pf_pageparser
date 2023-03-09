<?php

namespace Pforret\PfPageparser\Tests;

use Pforret\PfPageparser\PfPageparser;
use PHPUnit\Framework\TestCase;

class PageparserTest extends TestCase
{
    public function test_config()
    {
        $pp = new PfPageparser(['TestValue' => 1]);
        $cf = $pp->get_config();
        $this->assertArrayHasKey('cacheTtl', $cf, 'CacheTime should exist');
        $this->assertEquals(3600, $cf['cacheTtl'], 'CacheTime should have default value 3600');
        $this->assertEquals(1, $cf['TestValue'], 'TestValue should have value 1');
    }

    public function test_input_from_string()
    {
        $pp = new PfPageparser();
        $pp->load_fom_string('one,two,three')
            ->split_chunks(',');
        $this->assertEquals(count($pp->get_chunks()), 3);
    }

    public function test_input_from_file()
    {
        $pp = new PfPageparser();
        $string = $pp->load_from_file('tests/content/input1.html')
            ->trim('<body', '</body')
            ->raw();
        $this->assertTrue(strpos($string, '2018') > 0, 'Get raw contents');

        $pp->load_from_file('tests/content/input1.html')
            ->trim('<body', '</body')
            ->split_chunks('</tr>')
            ->filter_chunks(['$']);
        $this->assertEquals(count($pp->get_chunks()), 3);

        $pp->load_from_file('tests/content/input1.html')
            ->trim('<body', '</body')
            ->cleanup_html()
            ->split_chunks('</tr>')
            ->filter_chunks(['$']);
        $this->assertEquals(count($pp->get_chunks()), 3);

        $pp->load_from_file('tests/content/input1.html')
            ->trim('<body', '</body')
            ->split_chunks('</tr>', true)
            ->filter_chunks(['$']);
        $this->assertEquals(count($pp->get_chunks()), 3);

        $results = $pp->load_from_file('tests/content/input1.html')
            ->trim('<body', '</body')
            ->split_chunks('</tr>')
            ->filter_chunks(['$'])
            ->parse_fom_chunks('|<td>(.*)</td>|')
            ->results();
        $this->assertEquals(count($results), 3);
        $this->assertEquals($results[2][0], '20$');
    }

    public function test_from_url()
    {
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

    public function test_no_resolve()
    {
        $pp = new PfPageparser();
        $pp->load_from_url('https://non_existant.wikipedia.org/');
        $this->assertEmpty($pp->get_content(), 'HTML retrieved and trimmed');
        $pp->load_from_url('not_http://www.wikipedia.org/');
        $this->assertEmpty($pp->get_content(), 'HTML retrieved and trimmed');
    }
}
