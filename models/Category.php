<?php
// The "categories" table
class Category extends Model
{
    public function all()
    {
        return $this->getRows('SELECT category_id, name FROM categories ORDER BY name');
    }
}
