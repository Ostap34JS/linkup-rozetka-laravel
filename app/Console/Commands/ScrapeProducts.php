<?php

namespace App\Console\Commands;

use App\Category;
use App\Image;
use App\Product;
use Carbon\Carbon;
use Goutte\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape:products {url}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * @var string $url
     */
    private $url;

    /**
     * @var Client $client
     */
    private $client;

    /**
     * @var array $images
     */
    private $images;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->client = new Client();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->alert('Scraping in progress, please wait...');

        $url = $this->argument('url');

        $this->url = $url;

        $crawler = $this->client->request('GET', $url);

        $categories =  $this->getCategories($crawler);

        //Get pagination pages count
        $count = $crawler->filter('ul[name="paginator"] li')->count();
        //Only < 3 pages
        $pages = $count >= 3 ? 3 : $count;

        $urls = $this->getProductUrls($crawler, $pages);

        //Create a nice progress bar
        $bar = $this->output->createProgressBar(count($urls));
        $bar->setFormat('very_verbose');
        $bar->start();

        //Get all product data from links
        $products = collect();
        foreach ($urls as $key => $url){
            $products->push($this->getProduct($url));
            $bar->advance();
        }

        //Last product before inserting new products
        $lastProduct = Product::select('id')->latest('id')->limit(1)->first();

        Product::insert($products->toArray());

        //Get ids of inserted products
        $ids = Product::select('id', 'original_id')
            ->where('id', '>', $lastProduct->id ?? 0)
            ->get()
            ->pluck('id', 'original_id');

        //Prepare images array - replace original_id to id
        $images = $this->images;
        foreach ($images as $key => $image){
            $originalId = $image['product_id'];
            //Get id by original_id
            $images[$key]['product_id'] = $ids[$originalId];
        }

        Image::insert($images);

        //Format `product_category` table data
        $productsCategories = [];
        foreach ($ids as $id){
            foreach ($categories as $category){
                $productsCategories[] = [
                    'product_id'  => $id,
                    'category_id' => $category
                ];
            }
        }

        DB::table('product_category')->insert($productsCategories);

        $bar->finish();
    }

    /**
     * @param Crawler $crawler
     * @return array
     */
    private function getCategories(Crawler $crawler)
    {
        $rootCategoryTitle = $this->getRootCategoryTitle($crawler);

        $rootCategory = Category::firstOrCreate(['title' => $rootCategoryTitle]);

        return $crawler
            ->filter('.filter-active > ul > li> a.filter-active-i-link')
            ->each(function ($node) use ($rootCategory) {
                return Category::firstOrCreate([
                    'title' => $node->text(),
                    'parent_id' => $rootCategory->id
                ])->id;
            });
    }

    /**
     * @param Crawler $crawler
     * @return string
     */
    private function getRootCategoryTitle(Crawler $crawler)
    {
        $breadcrumb = $crawler->filter('li.breadcrumbs-catalog-i')->last();

        $rootCategoryTitle = trim($breadcrumb->text());
        if ($rootCategoryTitle === 'Підбір за параметрами'){
            $rootCategoryTitle = trim($breadcrumb->previousAll()->first()->text());
        }

        return $rootCategoryTitle;
    }

    /**
     * Get product urls
     * @param Crawler $crawler
     * @param int $pages
     * @return array
     */
    private function getProductUrls(Crawler $crawler, $pages = 3)
    {
        $urls = [];
        for ($i = 0; $i <= $pages; $i++) {
            if ($i != 0) {
                $baseUrl = rtrim($this->url, '/');

                //Add page to params
                $explodedUrl = explode('/', $baseUrl);
                $params = "page=$i;".array_pop($explodedUrl);
                array_push($explodedUrl, $params);
                $baseUrl = implode('/', $explodedUrl);

                $crawler = $this->client->request(
                    'GET',
                    $baseUrl
                );
            }

            $crawler->filter('div[name="goods_list"] div.g-i-tile-i-title a')->each(
                function ($node) use (&$urls) {
                    $urls[] = $node->attr('href');
                }
            );
        }

        return $urls;
    }

    /**
     * @param string $url
     * @return array
     */
    private function getProduct(string $url)
    {
        $crawler = $this->client->request('GET', $url);

        $title = $crawler->filter('h1')->text();

        $text = $crawler->filter('div.goods-description-content > p');
        //only first 2 paragraphs, if exits
        $description  = $text->count() >= 1 ? $text->eq(0)->html() : '';
        $description .= $text->count() >= 2 ? $text->eq(1)->html() : '';

        $price = intval($crawler->filter('.detail-price-uah')->text());

        $json = json_decode(
            str_replace(
                '&q;',
                '"',
                $crawler->filter('script#serverApp-state')->text()
            )
        );

        $jsonKeys = array_keys(get_object_vars($json));

        //Find key what contains "getPageJsonData"
        foreach ($jsonKeys as $key => $value){
            if (Str::contains($value, 'getPageJsonData')){
                $jsonKey = $value;
                break;
            }
        }

        $data = $json->{$jsonKey}->body->content;

        $originalId = $data->goods->record->id;

        $images = $data->images->images;

        foreach ($images as $image){
            $this->images[] = [
                'original'   => $image->original->src,
                'big'        => $image->big->src,
                'preview'    => $image->preview->src,
                'large'      => $image->large->src,
                'product_id' => $originalId
            ];
        }

        return [
            'title'       => $title,
            'description' => $description,
            'price'       => $price,
            'original_id' => $originalId,
            'created_at'  => Carbon::now(),
            'updated_at'  => Carbon::now(),
        ];
    }
}
