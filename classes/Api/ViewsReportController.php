<?php

/**
 * @package    Grav\Plugin\Views
 *
 * @copyright  Copyright (C) 2014 - 2026 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Plugin\Views\Api;

use Grav\Plugin\Api\Controllers\AbstractApiController;
use Grav\Plugin\Api\Response\ApiResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Lightweight API endpoint backing the Admin2 dashboard widget.
 *
 * The widget only needs the top tracked pages, so it has its own cheap route
 * rather than calling the generic /reports endpoint, which runs a full-site XSS
 * scan and YAML lint on every request. This class is only ever loaded when the
 * API plugin is present, so the AbstractApiController parent always resolves.
 */
class ViewsReportController extends AbstractApiController
{
    private const PERMISSION_READ = 'api.reports.read';
    private const DEFAULT_LIMIT = 6;
    private const MAX_LIMIT = 50;

    /**
     * GET /views/top-pages - Return the most viewed pages, highest first.
     */
    public function topPages(ServerRequestInterface $request): ResponseInterface
    {
        $this->requirePermission($request, self::PERMISSION_READ);

        $limit = $this->resolveLimit($request);

        $items = $this->grav['views']->getAll(null, $limit, 'desc');

        $total = 0;
        foreach ($items as $item) {
            $total += (int) ($item['count'] ?? 0);
        }

        return ApiResponse::create([
            'items' => $items,
            'total' => $total,
        ]);
    }

    /**
     * Read and clamp the optional `limit` query parameter.
     */
    private function resolveLimit(ServerRequestInterface $request): int
    {
        $requested = $request->getQueryParams()['limit'] ?? null;
        if (!is_numeric($requested)) {
            return self::DEFAULT_LIMIT;
        }

        $limit = (int) $requested;
        if ($limit < 1) {
            return self::DEFAULT_LIMIT;
        }

        return min($limit, self::MAX_LIMIT);
    }
}
