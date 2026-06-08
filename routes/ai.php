<?php

use App\Mcp\Servers\CDTServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('cdt', CDTServer::class);
