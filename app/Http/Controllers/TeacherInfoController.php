<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class TeacherInfoController extends Controller
{
    public function scrape(): JsonResponse
    {
        ini_set('max_execution_time', 300); // For long-running scraping

        $baseUrl = 'https://faculty.daffodilvarsity.edu.bd';
        $firstPage = $baseUrl . '/teachers/cse';

        $visitedPages = [];
        $facultyLinks = [];
        $nextPages = [$firstPage];

        // Step 1: Get all paginated pages and collect faculty profile links
        while (!empty($nextPages)) {
            $pageUrl = array_shift($nextPages);

            if (in_array($pageUrl, $visitedPages)) {
                continue;
            }
            $visitedPages[] = $pageUrl;

            $response = Http::get($pageUrl);
            if (!$response->successful()) continue;

            $html = $response->body();
            $crawler = new Crawler($html);

            // Each teacher is in a li.item-designer
            $crawler->filter('li.item-designer')->each(function (Crawler $node) use (&$facultyLinks, $baseUrl) {
                $text = $node->text();

                // Skip teachers on Study Leave or On Leave
                if (str_contains($text, '(Study Leave)') || str_contains($text, '(On Leave)')) {
                    return;
                }

                $linkNode = $node->filter('a.fox');
                if ($linkNode->count() === 0) return;

                $href = $linkNode->attr('href');
                if (!$href) return;

                if (!str_starts_with($href, 'http')) {
                    $href = $baseUrl . $href;
                }

                $facultyLinks[] = $href;
            });

            // Handle pagination
            $paginationLinks = $crawler->filter('.pagination a')->each(function (Crawler $node) use ($baseUrl) {
                $href = $node->attr('href');
                if (!$href) return null;
                if (!str_starts_with($href, 'http')) {
                    $href = $baseUrl . $href;
                }
                return $href;
            });

            foreach (array_filter($paginationLinks) as $link) {
                if (!in_array($link, $visitedPages) && !in_array($link, $nextPages)) {
                    $nextPages[] = $link;
                }
            }
        }

        // Remove duplicates
        $facultyLinks = array_unique($facultyLinks);
        $facultyData = [];

        // Step 2: Visit each profile link and extract detailed info
        foreach ($facultyLinks as $link) {
            $profileResponse = Http::get($link);

            $profileHtml = $profileResponse->body();
            $profileCrawler = new Crawler($profileHtml);

            // Extract name and designation
            $name = $profileCrawler->filter('.profile-header span')->eq(0)->text('');
            $designation = $profileCrawler->filter('.profile-header span')->eq(1)->text('');

            // Extract image
            $imageUrl = $profileCrawler->filter('div.profaile-pic img')->count()
                ? $profileCrawler->filter('div.profaile-pic img')->attr('src')
                : null;

            if ($imageUrl && !str_starts_with($imageUrl, 'http')) {
                $imageUrl = $baseUrl . $imageUrl;
            }

            // Extract personal info
            $info = [];
            $rows = $profileCrawler->filter('.tab-content-body > div');
            $rows->each(function (Crawler $row) use (&$info) {
                $label = trim($row->filter('.profile-row-left')->text(''));
                $value = trim($row->filter('.profile-row-right')->text(''));
                if (!empty($label)) {
                    $info[$label] = $value;
                }
            });

            $facultyData[] = [
                'name' => $name,
                'teacher' => null,
                'designation' => $designation,
                'employee_id' => $info['Employee ID'] ?? null,
                'department' => $info['Department'] ?? null,
                'faculty' => $info['Faculty'] ?? null,
                'email' => $info['E-mail'] ?? null,
                'phone' => $info['Phone'] ?? null,
                'cell_phone' => $info['Cell-Phone'] ?? null,
                'personal_website' => $info['Personal Webpage'] ?? null,
                'image_url' => $imageUrl ?? null,
            ];
        }

//        foreach ($facultyData as $faculty) {
//            Teacher::updateOrCreate($faculty);
//        }

        return response()->json($facultyData);
    }
}

