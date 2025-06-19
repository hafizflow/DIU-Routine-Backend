<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class CourseController extends Controller
{
    public function scrape(): JsonResponse
    {
        // Fetch the webpage
        $response = Http::get('https://daffodilvarsity.edu.bd/department/cse/program/bsc-in-cse');

        // Check if the request was successful
        if ($response->successful()) {
            // Get the HTML content
            $html = $response->body();

            // Initialize the DOM Crawler
            $crawler = new Crawler($html);

            // Extract courses from all tables
            $courses = $crawler->filter('table')->each(function (Crawler $table) {
                return $table->filter('tr')->each(function (Crawler $row) {
                    // Skip header rows or rows with "Total"
                    $rowText = $row->text();
                    if (str_contains(strtolower($rowText), 'total') || trim(strtolower($rowText)) === 'code') {
                        return null;
                    }

                    // Extract course code (1st or 2nd column depending on table structure)
                    $codeNode = $row->filter('td:nth-child(1) p, td:nth-child(1), td:nth-child(2) p, td:nth-child(2)');
                    $courseCode = $codeNode->count() ? preg_replace('/[\s-]+/', '', $codeNode->text()) : null;

                    // Extract course title (2nd or 3rd column depending on table structure)
                    $titleNode = $row->filter('td:nth-child(2) p, td:nth-child(2), td:nth-child(3) p, td:nth-child(3)');
                    $courseTitle = $titleNode->count() ? trim($titleNode->text()) : null;

                    // Extract course type (3rd or 4th column depending on table structure)
                    $typeNode = $row->filter('td:nth-child(3) p, td:nth-child(3), td:nth-child(4) p, td:nth-child(4)');
                    $courseType = $typeNode->count() ? trim($typeNode->text()) : null;

                    // Extract credits (4th or 5th column depending on table structure)
                    $creditsNode = $row->filter('td:nth-child(4) p, td:nth-child(4), td:nth-child(5) p, td:nth-child(5)');
                    $credits = $creditsNode->count() ? trim($creditsNode->text()) : null;

                    // Validate data to exclude unwanted entries
                    if ($courseCode && $courseTitle && $courseType && $credits) {
                        // Check if course_code is not a single number and not "Code"
                        if (strtolower($courseCode) === 'code' || is_numeric($courseCode) && !preg_match('/[A-Za-z]/', $courseCode)) {
                            return null;
                        }
                        // Check if course_title or course_type are not credit-like values
                        if (preg_match('/^\d+(\.\d+)?\s*Credits$/', $courseTitle) || preg_match('/^\d+(\.\d+)?\s*Credits$/', $courseType)) {
                            return null;
                        }
                        return [
                            'course_code' => $courseCode,
                            'course_title' => $courseTitle,
//                            'course_type' => $courseType,
                            'credits' => (float)$credits,
                        ];
                    }

                    return null;
                });
            });

            // Flatten and filter out null entries
            $courses = array_merge(...$courses);
            $courses = array_filter($courses);

            // Update or create records in the database
//            foreach ($courses as $course) {
//                Course::updateOrCreate($course);
//            }

            // Return the scraped data as JSON
            return response()->json(array_values($courses));
        }

        // Handle errors
        return response()->json([
            'error' => 'Failed to fetch the webpage',
            'status' => $response->status(),
        ], $response->status());
    }
}
