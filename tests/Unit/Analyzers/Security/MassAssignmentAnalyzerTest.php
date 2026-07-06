<?php

namespace LaravelAssist\Assistant\Tests\Unit\Analyzers\Security;

use LaravelAssist\Assistant\Analyzers\Security\MassAssignmentAnalyzer;
use LaravelAssist\Assistant\Support\LaravelInspector;
use LaravelAssist\Assistant\Tests\TestCase;
use Illuminate\Support\Facades\File;

class MassAssignmentAnalyzerTest extends TestCase
{
    protected string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = sys_get_temp_dir() . '/assistant_test_' . uniqid();
        File::makeDirectory($this->testDir . '/app/Models', 0755, true, true);
        File::put($this->testDir . '/composer.json', json_encode([
            'autoload' => ['psr-4' => ['App\\' => 'app/']],
        ]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testDir);
        parent::tearDown();
    }

    public function test_detects_model_with_empty_guarded_and_no_fillable(): void
    {
        $modelContent = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];
}';
        File::put($this->testDir . '/app/Models/User.php', $modelContent);

        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->testDir);

        $analyzer = new MassAssignmentAnalyzer();
        $findings = $analyzer->analyze($inspector);

        $this->assertNotEmpty($findings);
        $this->assertEquals('critical', $findings[0]['severity']);
        $this->assertEquals('mass_assignment', $findings[0]['type']);
    }

    public function test_no_findings_for_secure_model(): void
    {
        $modelContent = '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ["name", "email"];
}';
        File::put($this->testDir . '/app/Models/User.php', $modelContent);

        $inspector = new LaravelInspector(app('files'));
        $inspector->setBasePath($this->testDir);

        $analyzer = new MassAssignmentAnalyzer();
        $findings = $analyzer->analyze($inspector);

        $this->assertEmpty($findings);
    }

    public function test_returns_correct_metadata(): void
    {
        $analyzer = new MassAssignmentAnalyzer();

        $this->assertEquals('Mass Assignment', $analyzer->getName());
        $this->assertEquals('security', $analyzer->getCategory());
        $this->assertNotEmpty($analyzer->getDescription());
    }
}
