<?php

declare(strict_types=1);

namespace IgFeed\Lib;

enum MediaType : string
{
    case Image = 'IMAGE';
    case Video = 'VIDEO';
    case CarouselAlbum = 'CAROUSEL_ALBUM';
}
