<?php

// Routes
$app->get('/', 'SkypeController:index');
$app->post('/', 'SkypeController:message');
