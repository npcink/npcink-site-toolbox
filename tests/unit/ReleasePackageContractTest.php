<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ReleasePackageContractTest extends TestCase
{
    private const PACKAGE_SLUG = 'magick-toolbox';

    /** @var string */
    private $temporary_root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporary_root = sys_get_temp_dir() . '/mabox release contract ' . bin2hex(random_bytes(6));
        $this->assertTrue(mkdir($this->temporary_root, 0700, true));
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->temporary_root);
        parent::tearDown();
    }

    public function test_release_commands_and_distignore_define_one_packaging_contract(): void
    {
        $root = $this->root();
        $composer = json_decode((string) file_get_contents($root . '/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('bash bin/build-release-zip.sh', $composer['scripts']['release:build']);
        $this->assertSame('bash bin/verify-release-zip.sh', $composer['scripts']['release:verify']);
        $this->assertTrue(is_executable($root . '/bin/build-release-zip.sh'));
        $this->assertTrue(is_executable($root . '/bin/verify-release-zip.sh'));

        $rules = array_values(array_filter(array_map(
            'trim',
            preg_split('/\R/', (string) file_get_contents($root . '/.distignore')) ?: array()
        ), static function (string $line): bool {
            return $line !== '' && strpos($line, '#') !== 0;
        }));
        foreach (array(
            'bin',
            'tests',
            'docs',
            'docs-site',
            'vendor',
            'node_modules',
            'stubs',
            'vite/*/src',
            'vite/*/dist/.vite',
            'magick-toolbox-*.zip',
            'magick-toolbox.zip',
            'wp-magick-toolbox-*.zip',
            'wp-magick-toolbox.zip',
            '*.zip',
            '*.sha256',
        ) as $rule) {
            $this->assertContains($rule, $rules);
        }

        $gitignore_rules = array_values(array_filter(array_map(
            'trim',
            preg_split('/\R/', (string) file_get_contents($root . '/.gitignore')) ?: array()
        ), static function (string $line): bool {
            return $line !== '' && strpos($line, '#') !== 0;
        }));
        foreach (array(
            'magick-toolbox-*.zip',
            'magick-toolbox.zip',
            'magick-toolbox.zip.sha256',
            'wp-magick-toolbox-*.zip',
            'wp-magick-toolbox.zip',
            'wp-magick-toolbox.zip.sha256',
        ) as $rule) {
            $this->assertContains($rule, $gitignore_rules);
        }

        $build = (string) file_get_contents($root . '/bin/build-release-zip.sh');
        $verify = (string) file_get_contents($root . '/bin/verify-release-zip.sh');
        $this->assertStringContainsString('PLUGIN_SLUG="magick-toolbox"', $build);
        $this->assertStringContainsString('magick-toolbox.zip', $build);
        $this->assertStringNotContainsString('PLUGIN_SLUG="wp-magick-toolbox"', $build);
        $this->assertStringContainsString('PLUGIN_SLUG="magick-toolbox"', $verify);
        $this->assertStringNotContainsString('PLUGIN_SLUG="wp-magick-toolbox"', $verify);
        $this->assertStringContainsString('rsync -a --exclude-from="$DISTIGNORE"', $build);
        $this->assertStringContainsString('mktemp -d', $build);
        $this->assertStringContainsString('trap cleanup', $build);
        $this->assertStringContainsString('"$VERIFY_SCRIPT" "$temporary_zip"', $build);
        foreach (array(
            'vite/admin/dist/index.js',
            'vite/admin/dist/index.css',
            'vite/count/dist/index.js',
            'vite/count/dist/index.css',
        ) as $asset) {
            $this->assertStringContainsString($asset, $build);
        }
    }

    public function test_verifier_accepts_a_valid_fixture_with_spaces_and_reports_release_facts(): void
    {
        $archive = $this->createArchive('9.8.7');
        $checksum = hash_file('sha256', $archive);
        $this->assertIsString($checksum);
        $this->assertSame(
            $checksum . '  ' . basename($archive) . "\n",
            file_get_contents($archive . '.sha256')
        );

        $result = $this->runCommand(array('bash', $this->root() . '/bin/verify-release-zip.sh', $archive));

        $this->assertSame(0, $result['status'], $result['output']);
        $this->assertMatchesRegularExpression(
            '/entries=\d+ size=\d+ bytes sha256=[0-9a-f]{64} version=9\.8\.7/',
            $result['output']
        );
    }

    public function test_verifier_rejects_vite_source_and_version_drift(): void
    {
        $source_archive = $this->createArchive('9.8.7', '9.8.7', array(
            'vite/admin/src/leak.ts' => 'export const leaked = true;',
        ));
        $source_result = $this->runCommand(array('bash', $this->root() . '/bin/verify-release-zip.sh', $source_archive));
        $this->assertNotSame(0, $source_result['status']);
        $this->assertStringContainsString('vite release path is outside admin/count dist', $source_result['output']);

        $version_archive = $this->createArchive('9.8.7', '9.8.8');
        $version_result = $this->runCommand(array('bash', $this->root() . '/bin/verify-release-zip.sh', $version_archive));
        $this->assertNotSame(0, $version_result['status']);
        $this->assertStringContainsString('version mismatch', $version_result['output']);
    }

    public function test_verifier_rejects_a_mismatched_checksum_sidecar(): void
    {
        $archive = $this->createArchive('9.8.7');
        file_put_contents($archive . '.sha256', str_repeat('0', 64) . '  ' . basename($archive) . "\n");

        $result = $this->runCommand(array('bash', $this->root() . '/bin/verify-release-zip.sh', $archive));
        $this->assertNotSame(0, $result['status']);
        $this->assertStringContainsString('checksum mismatch', $result['output']);
    }

    public function test_verifier_rejects_a_wrong_sidecar_filename_and_extra_lines(): void
    {
        $wrong_name_archive = $this->createArchive('9.8.7');
        $wrong_name_checksum = hash_file('sha256', $wrong_name_archive);
        $this->assertIsString($wrong_name_checksum);
        file_put_contents($wrong_name_archive . '.sha256', $wrong_name_checksum . "  wrong-name.zip\n");

        $wrong_name_result = $this->runCommand(array(
            'bash', $this->root() . '/bin/verify-release-zip.sh', $wrong_name_archive,
        ));
        $this->assertNotSame(0, $wrong_name_result['status']);
        $this->assertStringContainsString('checksum sidecar filename must match archive basename', $wrong_name_result['output']);

        $extra_line_archive = $this->createArchive('9.8.7');
        $extra_line_checksum = hash_file('sha256', $extra_line_archive);
        $this->assertIsString($extra_line_checksum);
        $valid_line = $extra_line_checksum . '  ' . basename($extra_line_archive) . "\n";
        file_put_contents($extra_line_archive . '.sha256', $valid_line . "unexpected extra line\n");

        $extra_line_result = $this->runCommand(array(
            'bash', $this->root() . '/bin/verify-release-zip.sh', $extra_line_archive,
        ));
        $this->assertNotSame(0, $extra_line_result['status']);
        $this->assertStringContainsString('checksum sidecar must contain exactly one line', $extra_line_result['output']);
    }

    public function test_verifier_rejects_sensitive_dotfiles_and_vite_metadata_at_any_depth(): void
    {
        $forbidden_files = array(
            '.env.production' => 'forbidden release file',
            'admin/nested/.env.local' => 'forbidden release file',
            '.phpunit.result.cache' => 'forbidden release file',
            'admin/nested/.phpunit.result.cache' => 'forbidden release file',
            '.DS_Store' => 'forbidden release file',
            'admin/nested/.DS_Store' => 'forbidden release file',
            'vite/admin/dist/.vite/manifest.json' => 'forbidden release path',
            'vite/count/dist/.vite/dependencies.json' => 'forbidden release path',
        );

        foreach ($forbidden_files as $relative_path => $expected_error) {
            $archive = $this->createArchive('9.8.7', null, array($relative_path => 'must not ship'));
            $result = $this->runCommand(array(
                'bash', $this->root() . '/bin/verify-release-zip.sh', $archive,
            ));

            $this->assertNotSame(0, $result['status'], $relative_path);
            $this->assertStringContainsString($expected_error, $result['output'], $relative_path);
            $reported_path = strpos($relative_path, '/.vite/') !== false
                ? dirname($relative_path)
                : $relative_path;
            $this->assertStringContainsString($reported_path, $result['output'], $relative_path);
        }
    }

    public function test_verifier_rejects_missing_files_multiple_roots_and_path_traversal(): void
    {
        $missing_archive = $this->createArchive('9.8.7');
        $delete_result = $this->runCommand(array(
            'zip', '-q', '-d', $missing_archive, self::PACKAGE_SLUG . '/LICENSE',
        ));
        $this->assertSame(0, $delete_result['status'], $delete_result['output']);
        $missing_result = $this->runCommand(array('bash', $this->root() . '/bin/verify-release-zip.sh', $missing_archive));
        $this->assertNotSame(0, $missing_result['status']);
        $this->assertStringContainsString('missing required release file: LICENSE', $missing_result['output']);

        $activation_archive = $this->createArchive('9.8.7');
        $delete_activation_result = $this->runCommand(array(
            'zip', '-q', '-d', $activation_archive, self::PACKAGE_SLUG . '/includes/class-magick-mixture.php',
        ));
        $this->assertSame(0, $delete_activation_result['status'], $delete_activation_result['output']);
        $activation_result = $this->runCommand(array(
            'bash', $this->root() . '/bin/verify-release-zip.sh', $activation_archive,
        ));
        $this->assertNotSame(0, $activation_result['status']);
        $this->assertStringContainsString(
            'missing required release file: includes/class-magick-mixture.php',
            $activation_result['output']
        );

        $rest_registry_archive = $this->createArchive('9.8.7');
        $delete_rest_registry_result = $this->runCommand(array(
            'zip',
            '-q',
            '-d',
            $rest_registry_archive,
            self::PACKAGE_SLUG . '/includes/class-mabox-rest-route-registry.php',
        ));
        $this->assertSame(0, $delete_rest_registry_result['status'], $delete_rest_registry_result['output']);
        $rest_registry_result = $this->runCommand(array(
            'bash', $this->root() . '/bin/verify-release-zip.sh', $rest_registry_archive,
        ));
        $this->assertNotSame(0, $rest_registry_result['status']);
        $this->assertStringContainsString(
            'missing required release file: includes/class-mabox-rest-route-registry.php',
            $rest_registry_result['output']
        );

        $activation_callback_archive = $this->createArchive('9.8.7');
        $delete_callback_result = $this->runCommand(array(
            'zip',
            '-q',
            '-d',
            $activation_callback_archive,
            self::PACKAGE_SLUG . '/admin/partials/optimize/site/category_link_simplify.php',
        ));
        $this->assertSame(0, $delete_callback_result['status'], $delete_callback_result['output']);
        $activation_callback_result = $this->runCommand(array(
            'bash', $this->root() . '/bin/verify-release-zip.sh', $activation_callback_archive,
        ));
        $this->assertNotSame(0, $activation_callback_result['status']);
        $this->assertStringContainsString(
            'missing required release file: admin/partials/optimize/site/category_link_simplify.php',
            $activation_callback_result['output']
        );

        $multiple_roots_archive = $this->createArchive('9.8.7');
        $outside_file = $this->temporary_root . '/outside-root.txt';
        file_put_contents($outside_file, 'outside');
        $add_root_result = $this->runCommand(array('zip', '-q', $multiple_roots_archive, 'outside-root.txt'), $this->temporary_root);
        $this->assertSame(0, $add_root_result['status'], $add_root_result['output']);
        $multiple_roots_result = $this->runCommand(array('bash', $this->root() . '/bin/verify-release-zip.sh', $multiple_roots_archive));
        $this->assertNotSame(0, $multiple_roots_result['status']);
        $this->assertStringContainsString(
            'outside the single ' . self::PACKAGE_SLUG . '/ root',
            $multiple_roots_result['output']
        );

        $traversal_archive = $this->createArchive('9.8.7');
        $traversal_directory = $this->temporary_root . '/traversal';
        $this->assertTrue(mkdir($traversal_directory, 0700));
        file_put_contents($this->temporary_root . '/escape.php', '<?php');
        $add_traversal_result = $this->runCommand(array('zip', '-q', $traversal_archive, '../escape.php'), $traversal_directory);
        $this->assertSame(0, $add_traversal_result['status'], $add_traversal_result['output']);
        $traversal_result = $this->runCommand(array('bash', $this->root() . '/bin/verify-release-zip.sh', $traversal_archive));
        $this->assertNotSame(0, $traversal_result['status']);
        $this->assertStringContainsString('unsafe archive path: ../escape.php', $traversal_result['output']);
    }

    public function test_build_commit_window_survives_term_between_zip_and_sidecar_renames(): void
    {
        $project = $this->createBuildFixtureProject();
        $output_directory = $this->temporary_root . '/signal output';
        $this->assertTrue(mkdir($output_directory, 0700));
        $output = $output_directory . '/release pair with spaces.zip';
        $sidecar = $output . '.sha256';
        $this->assertNotFalse(file_put_contents($output, "previous zip\n"));
        $this->assertNotFalse(file_put_contents($sidecar, "previous checksum\n"));

        $move_state = $this->temporary_root . '/move state';
        $signal_marker = $this->temporary_root . '/signal sent';
        $bash_environment = $this->temporary_root . '/signal injection.bash';
        $this->assertNotFalse(file_put_contents($bash_environment, <<<'BASH'
mv() {
  command mv "$@"
  local move_status=$?
  if [ "$move_status" -ne 0 ]; then
    return "$move_status"
  fi

  local move_count=0
  if [ -f "$MV_SIGNAL_STATE" ]; then
    IFS= read -r move_count < "$MV_SIGNAL_STATE" || move_count=0
  fi
  move_count=$((move_count + 1))
  printf '%s\n' "$move_count" > "$MV_SIGNAL_STATE"

  if [ "$move_count" -eq 3 ]; then
    printf 'TERM after ZIP install\n' > "$MV_SIGNAL_SENT"
    kill -TERM "$$"
  fi
}
BASH
        ));

        $result = $this->runCommand(array(
            'env',
            'BASH_ENV=' . $bash_environment,
            'MV_SIGNAL_STATE=' . $move_state,
            'MV_SIGNAL_SENT=' . $signal_marker,
            'bash',
            $project . '/bin/build-release-zip.sh',
            $output,
        ));

        $this->assertSame(0, $result['status'], $result['output']);
        $this->assertFileExists($signal_marker);
        $this->assertSame('4', trim((string) file_get_contents($move_state)));
        $this->assertNotSame("previous zip\n", file_get_contents($output));
        $checksum = hash_file('sha256', $output);
        $this->assertIsString($checksum);
        $this->assertSame($checksum . '  ' . basename($output) . "\n", file_get_contents($sidecar));

        $verify_result = $this->runCommand(array(
            'bash', $project . '/bin/verify-release-zip.sh', $output,
        ));
        $this->assertSame(0, $verify_result['status'], $verify_result['output']);
        $this->assertSame(array(), glob($output_directory . '/.' . self::PACKAGE_SLUG . '-release.*') ?: array());
    }

    /**
     * @param array<string,string> $extra_files
     */
    private function createArchive(string $header_version, ?string $constant_version = null, array $extra_files = array()): string
    {
        static $fixture_number = 0;
        ++$fixture_number;

        $constant_version = $constant_version ?? $header_version;
        $fixture = $this->temporary_root . '/fixture ' . $fixture_number;
        $package = $fixture . '/' . self::PACKAGE_SLUG;
        $archive = $this->temporary_root . '/release package ' . $fixture_number . '.zip';

        $files = $this->requiredFixtureFiles($header_version, $constant_version) + $extra_files;

        $this->writeFixtureFiles($package, $files);

        $zip_result = $this->runCommand(array('zip', '-q', '-r', '-X', $archive, self::PACKAGE_SLUG), $fixture);
        $this->assertSame(0, $zip_result['status'], $zip_result['output']);

        $checksum = hash_file('sha256', $archive);
        $this->assertIsString($checksum);
        $this->assertNotFalse(file_put_contents(
            $archive . '.sha256',
            $checksum . '  ' . basename($archive) . "\n"
        ));

        return $archive;
    }

    private function createBuildFixtureProject(): string
    {
        $project = $this->temporary_root . '/build fixture project';
        $this->writeFixtureFiles($project, $this->requiredFixtureFiles('9.8.7', '9.8.7'));

        $bin_directory = $project . '/bin';
        $this->assertTrue(mkdir($bin_directory, 0700, true));
        foreach (array('build-release-zip.sh', 'verify-release-zip.sh') as $script) {
            $target = $bin_directory . '/' . $script;
            $this->assertTrue(copy($this->root() . '/bin/' . $script, $target));
            $this->assertTrue(chmod($target, 0700));
        }
        $this->assertTrue(copy($this->root() . '/.distignore', $project . '/.distignore'));

        return $project;
    }

    /**
     * @return array<string,string>
     */
    private function requiredFixtureFiles(string $header_version, string $constant_version): array
    {
        return array(
            'magick-tool-box.php' => "<?php\n/*\n * Plugin Name: Magick Toolbox\n * Version: {$header_version}\n */\ndefine('MAGICK_MIXTURE_VERSION', '{$constant_version}');\n",
            'readme.txt' => "=== Magick Toolbox ===\nStable tag: {$header_version}\n",
            'LICENSE' => 'GPL-2.0-or-later',
            'index.php' => "<?php\n",
            'uninstall.php' => "<?php\n",
            'admin/index.php' => "<?php\n",
            'includes/autoload.php' => "<?php\n",
            'includes/class-magick-mixture.php' => "<?php\n",
            'includes/class-magick-helpers.php' => "<?php\n",
            'includes/class-magick-rate-limiter.php' => "<?php\n",
            'includes/class-magick-audit-logger.php' => "<?php\n",
            'includes/class-magick-site-health.php' => "<?php\n",
            'includes/class-magick-mixture-tool.php' => "<?php\n",
            'includes/class-mabox-config-schema.php' => "<?php\n",
            'includes/class-magick-config-manager.php' => "<?php\n",
            'includes/class-mabox-rest-route-registry.php' => "<?php\n",
            'includes/interface-mabox-module.php' => "<?php\n",
            'admin/modules/loader.php' => "<?php\n",
            'admin/modules/metadata.php' => "<?php\n",
            'admin/modules/registry.php' => "<?php\nreturn array();\n",
            'admin/modules/tiers.php' => "<?php\nreturn array();\n",
            'admin/class-magick-mixture-admin.php' => "<?php\n",
            'admin/partials/optimize/site/category_link_simplify.php' => "<?php\n",
            'public/class-magick-mixture-public.php' => "<?php\n",
            'vite/admin/dist/index.js' => 'void 0;',
            'vite/admin/dist/index.css' => '.mabox{}',
            'vite/count/dist/index.js' => 'void 0;',
            'vite/count/dist/index.css' => '.mabox-count{}',
        );
    }

    /**
     * @param array<string,string> $files
     */
    private function writeFixtureFiles(string $root, array $files): void
    {
        foreach ($files as $relative_path => $contents) {
            $path = $root . '/' . $relative_path;
            $directory = dirname($path);
            if (!is_dir($directory)) {
                $this->assertTrue(mkdir($directory, 0700, true));
            }
            $this->assertNotFalse(file_put_contents($path, $contents));
        }
    }

    /**
     * @param string[] $arguments
     * @return array{status:int,output:string}
     */
    private function runCommand(array $arguments, ?string $working_directory = null): array
    {
        $command = implode(' ', array_map('escapeshellarg', $arguments));
        $pipes = array();
        $process = proc_open(
            $command,
            array(
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            ),
            $pipes,
            $working_directory
        );
        $this->assertIsResource($process);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return array(
            'status' => proc_close($process),
            'output' => (string) $stdout . (string) $stderr,
        );
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function removeTree(string $path): void
    {
        if (!file_exists($path) && !is_link($path)) {
            return;
        }
        if (is_file($path) || is_link($path)) {
            unlink($path);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $entry) {
            if ($entry->isDir() && !$entry->isLink()) {
                rmdir($entry->getPathname());
            } else {
                unlink($entry->getPathname());
            }
        }
        rmdir($path);
    }
}
