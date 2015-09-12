<?php

if (!file_exists('results')) {
    mkdir('results', 0777);
}

require 'watermark.php';
require 'rotate.php';
require 'flip.php';
require 'resize.php';
