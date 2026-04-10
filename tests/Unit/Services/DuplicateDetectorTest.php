<?php

namespace Nexus\RefManager\Tests\Unit\Services;

use Nexus\RefManager\Models\Author;
use Nexus\RefManager\Models\Document;
use Nexus\RefManager\Services\DuplicateDetector;
use Nexus\RefManager\Tests\TestCase;

class DuplicateDetectorTest extends TestCase
{
    public function testTierOneMatchesPubmedId(): void
    {
        Document::create([
            'title' => 'Known Trial',
            'pubmed_id' => '123456',
            'year' => 2024,
        ]);

        $detector = new DuplicateDetector();
        $result = $detector->check([
            'title' => 'Some Variant Title',
            'PMID' => '123456',
            'issued' => ['date-parts' => [[2024]]],
        ]);

        $this->assertTrue($result->isDuplicate);
        $this->assertSame('pubmed', $result->matchedBy);
    }

    public function testTierTwoMatchesTitleYearAuthor(): void
    {
        $document = Document::create([
            'title' => 'Machine Learning for Screening',
            'year' => 2023,
        ]);

        $author = Author::create([
            'family_name' => 'Smith',
            'given_name' => 'Jane',
        ]);

        $document->authors()->attach($author->id, ['author_order' => 1]);

        $detector = new DuplicateDetector();
        $result = $detector->check([
            'title' => 'Machine Learning for Screening',
            'issued' => ['date-parts' => [[2023]]],
            'author' => [
                ['family' => 'Smith', 'given' => 'J.'],
            ],
        ]);

        $this->assertTrue($result->isDuplicate);
        $this->assertSame('title_year_author', $result->matchedBy);
    }

    public function testTierThreeFlagsFuzzyCandidateForReview(): void
    {
        Document::create([
            'title' => 'Deep Learning for Clinical Text Classification',
            'year' => 2021,
        ]);

        $detector = new DuplicateDetector();
        $result = $detector->check([
            'title' => 'Deep Learning for Clinical Text Classification ',
            'issued' => ['date-parts' => [[2021]]],
            'author' => [],
        ]);

        $this->assertFalse($result->isDuplicate);
        $this->assertSame('fuzzy_title_year_review', $result->matchedBy);
        $this->assertGreaterThan(0.92, $result->confidence);
        $this->assertNotNull($result->existing);
    }
}


