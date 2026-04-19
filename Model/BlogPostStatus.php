<?php

declare(strict_types=1);

namespace MageOS\Blog\Model;

enum BlogPostStatus: int
{
    case Draft = 0;
    case Scheduled = 1;
    case Published = 2;
    case Archived = 3;
}
