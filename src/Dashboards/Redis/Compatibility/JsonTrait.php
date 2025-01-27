<?php
/**
 * This file is part of phpCacheAdmin.
 *
 * Copyright (c) Róbert Kelčák (https://kelcak.com/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace RobiNN\Pca\Dashboards\Redis\Compatibility;

use RedisException;

trait JsonTrait {
    /**
     * @return mixed
     *
     * @throws RedisException
     */
    public function jsonGet(string $key) {
        return $this->rawCommand('JSON.GET', $key);
    }

    /**
     * @param mixed $value
     *
     * @throws RedisException
     */
    public function jsonSet(string $key, $value): bool {
        $raw = $this->rawCommand('JSON.SET', $key, '$', $value);

        return $raw === true || $raw === 'OK';
    }
}
