<?php

namespace Luttje\UserCustomId\Contracts;

/**
 * Implement this on any model (owner or target) whose attributes may be
 * read by the {attribute}, {attribute-owner} and {attribute-relation}
 * format chunks.
 *
 * Formats are written by your users, so anything listed here can end up
 * in an id that the user gets to see. Models that do not implement this
 * interface expose nothing.
 */
interface ExposesCustomIdAttributes
{
    /**
     * The attributes that user-defined formats are allowed to read.
     *
     * Attributes of a related model are listed on the target model using
     * dot notation, e.g. 'category.name' allows {attribute-relation:category:name}.
     *
     * @return list<string>
     */
    public function getCustomIdAttributes(): array;
}
