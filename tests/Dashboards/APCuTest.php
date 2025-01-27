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

namespace Tests\Dashboards;

use JsonException;
use PHPUnit\Framework\TestCase;
use RobiNN\Pca\Dashboards\APCu\APCuDashboard;
use RobiNN\Pca\Helpers;
use RobiNN\Pca\Http;
use RobiNN\Pca\Template;

final class APCuTest extends TestCase {
    private Template $template;

    private APCuDashboard $dashboard;

    protected function setUp(): void {
        $this->template = new Template();
        $this->dashboard = new APCuDashboard($this->template);
    }

    /**
     * @throws JsonException
     */
    public function testDeleteKey(): void {
        $key = 'pu-test-delete-key';

        apcu_store($key, 'data');

        $_POST['delete'] = json_encode(base64_encode($key), JSON_THROW_ON_ERROR);

        $this->assertSame(
            $this->template->render('components/alert', ['message' => 'Key "'.$key.'" has been deleted.']),
            Helpers::deleteKey($this->template, static fn (string $key): bool => apcu_delete($key), true)
        );
        $this->assertFalse(apcu_exists($key));
    }

    /**
     * @throws JsonException
     */
    public function testDeleteKeys(): void {
        $key1 = 'pu-test-delete-key1';
        $key2 = 'pu-test-delete-key2';
        $key3 = 'pu-test-delete-key3';

        apcu_store($key1, 'data1');
        apcu_store($key2, 'data2');
        apcu_store($key3, 'data3');

        $_POST['delete'] = json_encode(array_map(static fn (string $key): string => base64_encode($key), [$key1, $key2, $key3]), JSON_THROW_ON_ERROR);

        $this->assertSame(
            $this->template->render('components/alert', ['message' => 'Keys has been deleted.']),
            Helpers::deleteKey($this->template, static fn (string $key): bool => apcu_delete($key), true)
        );
        $this->assertFalse(apcu_exists($key1));
        $this->assertFalse(apcu_exists($key2));
        $this->assertFalse(apcu_exists($key3));
    }

    public function testSetGetKey(): void {
        $keys = [
            'string' => ['original' => 'phpCacheAdmin', 'expected' => 'phpCacheAdmin'],
            'int'    => ['original' => 23, 'expected' => '23'],
            'float'  => ['original' => 23.99, 'expected' => '23.99'],
            'bool'   => ['original' => true, 'expected' => '1'],
            'null'   => ['original' => null, 'expected' => ''],
            'gzip'   => ['original' => gzcompress('test'), 'expected' => gzcompress('test')],
            'array'  => [
                'original' => ['key1', 'key2'],
                'expected' => 'a:2:{i:0;s:4:"key1";i:1;s:4:"key2";}',
            ],
            'object' => [
                'original' => (object) ['key1', 'key2'],
                'expected' => 'O:8:"stdClass":2:{s:1:"0";s:4:"key1";s:1:"1";s:4:"key2";}',
            ],
        ];

        foreach ($keys as $key => $value) {
            apcu_store('pu-test-'.$key, $value['original']);
        }

        $this->assertSame($keys['string']['expected'], Helpers::mixedToString(apcu_fetch('pu-test-string')));
        $this->assertSame($keys['int']['expected'], Helpers::mixedToString(apcu_fetch('pu-test-int')));
        $this->assertSame($keys['float']['expected'], Helpers::mixedToString(apcu_fetch('pu-test-float')));
        $this->assertSame($keys['bool']['expected'], Helpers::mixedToString(apcu_fetch('pu-test-bool')));
        $this->assertSame($keys['null']['expected'], Helpers::mixedToString(apcu_fetch('pu-test-null')));
        $this->assertSame($keys['gzip']['expected'], Helpers::mixedToString(apcu_fetch('pu-test-gzip')));
        $this->assertSame($keys['array']['expected'], Helpers::mixedToString(apcu_fetch('pu-test-array')));
        $this->assertSame($keys['object']['expected'], Helpers::mixedToString(apcu_fetch('pu-test-object')));

        foreach ($keys as $key => $value) {
            apcu_delete('pu-test-'.$key);
        }
    }

    public function testSaveKey(): void {
        $key = 'pu-test-save';

        $_POST['key'] = $key;
        $_POST['value'] = 'test-value';
        $_POST['encoder'] = 'none';

        Http::stopRedirect();
        $this->dashboard->saveKey();

        $this->assertSame('test-value', Helpers::mixedToString(apcu_fetch($key)));

        apcu_delete($key);
    }
}
