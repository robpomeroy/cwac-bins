<?php
use Symfony\Component\Panther\Client;
// Note: Panther extends Symfony's BrowserKit and DomCrawler components, so you
// can use all their features.
// BrowserKit: https://symfony.com/doc/current/components/browser_kit.html
// DomCrawler: https://symfony.com/doc/current/components/dom_crawler.html

require __DIR__.'/vendor/autoload.php';

// Load environment variables (from .env)                                     *
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$postcode = $_ENV['POSTCODE'];
$firstLine = $_ENV['FIRSTLINE'];

// Create a new Panther client (with headless browser)
$client = Client::createFirefoxClient();
//$client = Client::createChromeClient(); // Alternative to Firefox client
$crawler = $client->request('GET', 'https://my.cheshirewestandchester.gov.uk/en/AchieveForms/?form_uri=sandbox-publish://AF-Process-0187a2f6-15cb-413a-8a3f-b6d14d63da57/AF-Stage-e18b38ff-be8a-45f4-ac14-f8821024f0c4/definition.json&redirectlink=%2Fen&cancelRedirectLink=%2Fen&consentMessage=yes');

# The CWAC form is contained within an IFRAME. Wait for it to load.
$client->waitFor('#fillform-frame-1');

# Select and switch context to the IFRAME
$iframe = $crawler->filter('#fillform-frame-1');
$client->switchTo()->frame($iframe);

// Wait for the postcode_search field to be present
$client->waitFor('#postcode_search');

// DOM has been updated, so we need to re-fetch the crawler
$crawler = $client->getCrawler();

// Enter $postcode into the postcode_search field
$crawler->filter('#postcode_search')->sendKeys($postcode);

// Wait until Choose_Address select field has more than one option; it takes a
// while to populate.
$populated = false;
$tries = 0;
while (!$populated && $tries < 10) {
  sleep(1);
  $options = $crawler->filter('#Choose_Address option');
  if ($options->count() > 2) {
    $populated = true;
  }
  $tries++;
}

if(!$populated) {
  echo "ERROR: Choose_Address field was not populated after 10 seconds.\n";
  exit(1);
}

// Search through the options in the Choose_Address select field for the text
// that matches $firstLine. This will give us the unique property ID.
$searchThrough = $crawler->filter('#Choose_Address');
$propertyId = null;
foreach($searchThrough->children() as $child) {
  // Check if text matches $firstLine
  $result = preg_match("/^$firstLine.*/", $child->getText(), $matches);
  if($result === 1){
    $propertyId = $child->getAttribute('value');
  }
}
if(!$propertyId) {
  echo "ERROR: Could not find property ID for $firstLine.\n";
  exit(1);
}

// The whole form's ID is AF-Form-de343342-365a-4ce7-94e4-e00a4f7d21c7. Using
// this form, we can select an individual Choose_Address option.
$form = $crawler->filter('#AF-Form-de343342-365a-4ce7-94e4-e00a4f7d21c7')->form();
$chooseAddress = $form['Choose_Address'];

// Select the matched property
$chooseAddress->select($propertyId);

// Sleep for 5 seconds to load the bin schedule (normally takes ~4 seconds)
sleep(5);

// Ensure bin-schedule-content is populated
$client->waitFor('#bin-schedule-content');
$schedule = $crawler->filter('#bin-schedule-content')->text();

// Convenience text replacements
$schedule = str_replace('Domestic', 'Domestic (grey bin)', $schedule);
$schedule = str_replace('Food', 'Food (brown bin)', $schedule);
$schedule = str_replace('Garden', 'Garden (green bin)', $schedule);
$schedule = str_replace('Recycling', 'Recycling (red and blue lidded bins)', $schedule);

// Remove irrelevant line
$schedule = preg_replace('/Your collection.*/', "", $schedule);

// Insert some newlines
$schedule = preg_replace('/(Next collection.*)/', "$1\n", $schedule);

// Conversion for PowerShell script
$schedule = str_replace("\n", "|", $schedule);
echo $schedule;
