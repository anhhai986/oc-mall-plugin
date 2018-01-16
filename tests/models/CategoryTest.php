<?php namespace OFFLINE\Mall\Tests\Models;

use OFFLINE\Mall\Models\Category;
use PluginTestCase;

class CategoryTest extends PluginTestCase
{
    public $parent;
    public $child;
    public $nestedChild;

    public function setUp()
    {
        parent::setUp();
        $parent       = new Category();
        $parent->name = 'Parent';
        $parent->slug = 'parent';
        $parent->save();

        $this->parent = $parent;

        $child            = new Category();
        $child->name      = 'Child of parent';
        $child->parent_id = $parent->id;
        $child->slug      = 'child';
        $child->save();

        $this->child = $child;

        $nestedChild         = new Category();
        $nestedChild->name   = 'Child of the child';
        $nestedChild->parent = $child->id;
        $nestedChild->slug   = 'child';
        $nestedChild->save();
        // Overwrite the auto fixed child-2 slug
        $nestedChild->slug = 'child';
        $nestedChild->save();

        $this->nestedChild = $nestedChild;
    }

    public function test_it_finds_a_category_by_a_slug()
    {
        $hit = Category::getByNestedSlug('parent');
        $this->assertEquals($this->parent->id, $hit->id);

        $hit = Category::getByNestedSlug('parent/');
        $this->assertEquals($this->parent->id, $hit->id);

        $hit = Category::getByNestedSlug('/parent');
        $this->assertEquals($this->parent->id, $hit->id);
    }

    public function test_it_finds_a_category_by_a_nested_slug()
    {
        $hit = Category::getByNestedSlug('parent');
        $this->assertEquals($this->parent->id, $hit->id);

        $hit = Category::getByNestedSlug('parent/child');
        $this->assertEquals($this->child->id, $hit->id);

        $hit = Category::getByNestedSlug('parent/child/');
        $this->assertEquals($this->child->id, $hit->id);

        $hit = Category::getByNestedSlug('child');
        $this->assertNull($hit);

        $hit = Category::getByNestedSlug('parent/child/child');
        $this->assertEquals($this->nestedChild->id, $hit->id);

        $hit = Category::getByNestedSlug('child/child');
        $this->assertNull($hit);
    }
}