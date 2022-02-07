<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Models\Reply;
use Amrnn\CursorPaginator\Query\QueryMeta;
use Amrnn\CursorPaginator\Cursor;
use Amrnn\CursorPaginator\TargetsManager;

class QueryMetaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        factory(Reply::class, 10)->create();
    }

    /** @test */
    public function  it_gives_total_count()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [2, 3, 4])->get()->sortBy('id');
        $cursor = new Cursor('before', 5);
        $targetsManager = new TargetsManager($query);

        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager))->meta();
        $this->assertEquals(10, $meta['total']);
    }

    /** @test */
    public function  it_gives_correct_meta()
    {
        $query = Reply::orderBy('id')->orderBy('created_at');
        $items = Reply::whereIn('id', [1, 2, 3, 4])->get()->sortBy('id');
        $cursor = new Cursor('before', 5);
        $targetsManager = new TargetsManager($query);
        $nextItem = Reply::find(1);

        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager, $nextItem))->meta();

        $first = Reply::first();
        $last = Reply::latest()->first();
        $previous = Reply::find(2);
        $next = Reply::find(4);

        $this->assertEquals([
            'total' => 10,
            'first' => new Cursor('after_i', "1,{$first->created_at->timestamp}"),
            'last' => new Cursor('before_i', "10,{$last->created_at->timestamp}"),
            'previous' => new Cursor('before', "1,{$previous->created_at->timestamp}"),
            'next' => new Cursor('after', "4,{$next->created_at->timestamp}"),
            'has_next' => true,
            'has_previous' => false,
            'current' => $cursor,
            'next_item' => $nextItem
        ], $meta);
    }

    /** @test */
    public function it_returns_false_for_has_previous_if_there_are_no_previous_results()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [1, 2, 3])->get()->sortBy('id');
        $cursor = new Cursor('after_i', 1);
        $targetsManager = new TargetsManager($query);
        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager))->meta();

        $this->assertEquals(new Cursor('before', 1), $meta['previous']);
        $this->assertFalse($meta['has_previous']);
    }

    /** @test */
    public function it_returns_false_for_has_next_if_there_are_no_more_results()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [8, 9, 10])->get()->sortBy('id');
        $cursor = new Cursor('before_i', 10);
        $targetsManager = new TargetsManager($query);
        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager))->meta();

        $this->assertEquals(new Cursor('after', 10), $meta['next']);
        $this->assertFalse($meta['has_next']);

        $query = Reply::where('id', 1)->orderBy('id');
        $items = Reply::where('id', 1)->get();
        $cursor = new Cursor('after_i', 1);
        $targetsManager = new TargetsManager($query);
        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager))->meta();

        $this->assertEquals(new Cursor('after', 1), $meta['next']);
        $this->assertFalse($meta['has_next']);
    }

    /** @test */
    public function it_gives_next_item()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [1, 2, 3])->get()->sortBy('id');
        $nextItem = Reply::find('id', 4);
        $cursor = new Cursor('after_i', 1);
        $targetsManager = new TargetsManager($query);
        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager, $nextItem))->meta();

        $this->assertEquals($nextItem, $meta['next_item']);
    }

    /** @test */
    public function it_gives_no_total()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [1, 2, 3])->get()->sortBy('id');
        $nextItem = Reply::find('id', 4);
        $cursor = new Cursor('after_i', 1);
        $targetsManager = new TargetsManager($query);
        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager, $nextItem, false))->meta();

        $this->assertNull($meta['total']);
    }

    /** @test */
    public function it_gives_neither_first_nor_last()
    {
        $query = Reply::orderBy('id');
        $items = Reply::whereIn('id', [1, 2, 3])->get()->sortBy('id');
        $nextItem = Reply::find('id', 4);
        $cursor = new Cursor('after_i', 1);
        $targetsManager = new TargetsManager($query);
        $meta = (new QueryMeta($query, $items, $cursor, $targetsManager, $nextItem, true, false))->meta();

        $this->assertNotNull($meta['total']);
        $this->assertNull($meta['first']);
        $this->assertNull($meta['last']);
    }
}
