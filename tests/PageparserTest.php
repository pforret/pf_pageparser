<?php

namespace Pforret\PfPageparser\Tests;

use Pforret\PfPageparser\PfPageparser;
use PHPUnit\Framework\TestCase;


class PageparserTest extends TestCase
{

    /**
     *
     */
    public function test_config(){
        $pp=New PfPageparser(["TestValue" => 1]);
        $cf=$pp->get_config();
        $this->assertArrayHasKey("CacheTime",$cf,"CacheTime should exist");
        $this->assertEquals(3600,$cf["CacheTime"],"CacheTime should have default value 3600");
        $this->assertEquals(1,$cf["TestValue"],"TestValue should have value 1");
    }

    public function test_input1(){
        $pp=New PfPageparser();
        $pp->load_from_file("tests/content/input1.html")
            ->trim("<body","</body")
            ->split_chunks("</tr>")
            ->filter_chunks("$");
        $this->assertEquals(count($pp->get_chunks()),3);

        $pp->load_from_file("tests/content/input1.html")
            ->trim("<body","</body")
            ->split_chunks("</tr>",true)
            ->filter_chunks("$");
        $this->assertEquals(count($pp->get_chunks()),3);

        $results=$pp->load_from_file("tests/content/input1.html")
            ->trim("<body","</body")
            ->split_chunks("</tr>")
            ->filter_chunks("$")
            ->parse_fom_chunks("|<td>(.*)</td>|");
        $this->assertEquals(count($results),3);
        $this->assertEquals($results[2][0],"20$");

    }
}
