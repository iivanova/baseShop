<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;


final class ProductTest extends TestCase
{
	
	private function loadData($url) {
		return file_get_contents($url);
	}
	
	/** @test */
    public function notEmpty(): string
    {
		$data = $this->loadData('http://nginx-container');
		$this->assertNotEmpty($data);
		
		$number_of_products = substr_count($data, 'product-item');
		$this->assertGreaterThan(0, $number_of_products);	

		return $data;
    }
	
	/**
     * @depends notEmpty
     */
    public function testPrices(string $data): void
    {
       $this->assertNotEmpty($data);
	   
	   $doc = new DOMDocument();
	   $doc->loadHTML($data);
	   
	   $this->assertNotNull($doc);
	   
	   $xpath = new DomXpath($doc);
	   $this->assertNotNull($xpath);
	   
	   foreach ($xpath->query('//div[@product-item-price="1"]') as $rowNode) {
		$this->assertNotEmpty($rowNode->nodeValue);
		 $item_price = trim(substr($rowNode->nodeValue, strlen("Price: ")));
		 
		 $this->assertGreaterThan(0, $item_price);
       }
    }
	
	/**
     * @depends notEmpty
     */
    public function testNames(string $data): void
    {
       $this->assertNotEmpty($data);
	   
	   $doc = new DOMDocument();
	   $doc->loadHTML($data);
	   
	   $this->assertNotNull($doc);
	   
	   $xpath = new DomXpath($doc);
	   $this->assertNotNull($xpath);
	   
	   foreach ($xpath->query('//div[@product-item-name="1"]') as $rowNode) {
		$this->assertNotEmpty($rowNode->nodeValue);
		 $item_name = trim(substr($rowNode->nodeValue, strlen("Product: ")));
		 
		 $this->assertNotEmpty($item_name);
       }
    }
   
}
