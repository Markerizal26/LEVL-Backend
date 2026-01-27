<?php

declare(strict_types=1);

namespace Modules\Schemes\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Schemes\Models\Tag;

class TagSeederEnhanced extends Seeder
{
    public function run(): void
    {
        $this->command->info("\n🏷️  Creating realistic tags...");

        $tags = [
            // Programming Languages
            'PHP', 'JavaScript', 'Python', 'Java', 'TypeScript', 'Go', 'Rust', 'C++', 'C#', 'Ruby',
            'Swift', 'Kotlin', 'Dart', 'R', 'Scala', 'Elixir',
            
            // Frontend Technologies
            'React', 'Vue.js', 'Angular', 'Svelte', 'Next.js', 'Nuxt.js', 'HTML', 'CSS', 'SASS', 'Tailwind CSS',
            'Bootstrap', 'Material UI', 'Chakra UI',
            
            // Backend Technologies
            'Laravel', 'Node.js', 'Express.js', 'Django', 'Flask', 'Spring Boot', 'ASP.NET', 'Ruby on Rails',
            'FastAPI', 'NestJS', 'Symfony',
            
            // Databases
            'PostgreSQL', 'MySQL', 'MongoDB', 'Redis', 'Elasticsearch', 'SQLite', 'Oracle', 'SQL Server',
            'DynamoDB', 'Cassandra', 'Neo4j',
            
            // DevOps & Cloud
            'Docker', 'Kubernetes', 'AWS', 'Azure', 'Google Cloud', 'CI/CD', 'Jenkins', 'GitLab CI',
            'GitHub Actions', 'Terraform', 'Ansible', 'Nginx', 'Apache',
            
            // Data & AI
            'Machine Learning', 'Deep Learning', 'TensorFlow', 'PyTorch', 'Pandas', 'NumPy', 'Scikit-learn',
            'Data Visualization', 'Power BI', 'Tableau', 'Apache Spark',
            
            // Mobile Development
            'React Native', 'Flutter', 'iOS Development', 'Android Development', 'Xamarin', 'Ionic',
            
            // Design
            'Figma', 'Adobe XD', 'Sketch', 'Photoshop', 'Illustrator', 'InDesign', 'Blender',
            'UI Design', 'UX Research', 'Design Systems', 'Prototyping',
            
            // Methodologies & Practices
            'Agile', 'Scrum', 'Kanban', 'TDD', 'BDD', 'Clean Code', 'SOLID Principles', 'Design Patterns',
            'Microservices', 'RESTful API', 'GraphQL', 'WebSockets',
            
            // Soft Skills
            'Communication', 'Leadership', 'Problem Solving', 'Critical Thinking', 'Teamwork',
            'Time Management', 'Public Speaking', 'Negotiation',
            
            // Security
            'Ethical Hacking', 'Penetration Testing', 'OWASP', 'SSL/TLS', 'OAuth', 'JWT',
            
            // Testing
            'Unit Testing', 'Integration Testing', 'E2E Testing', 'Selenium', 'Jest', 'PHPUnit', 'Pytest',
            
            // Other Tools & Technologies
            'Git', 'GitHub', 'GitLab', 'VS Code', 'IntelliJ IDEA', 'Postman', 'Jira', 'Confluence',
            'Slack', 'Notion', 'Linux', 'Windows Server',
            
            // Domains
            'E-commerce', 'FinTech', 'HealthTech', 'EdTech', 'Gaming', 'IoT', 'Blockchain', 'Cryptocurrency',
            'Artificial Intelligence', 'Augmented Reality', 'Virtual Reality',
        ];

        $tagData = [];
        $created = 0;

        foreach ($tags as $tagName) {
            $slug = Str::slug($tagName);
            
            if (!Tag::where('slug', $slug)->exists()) {
                $tagData[] = [
                    'name' => $tagName,
                    'slug' => $slug,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $created++;
            }
        }

        if (!empty($tagData)) {
            foreach (array_chunk($tagData, 50) as $chunk) {
                DB::table('tags')->insertOrIgnore($chunk);
            }
        }

        $total = Tag::count();
        $this->command->info("  ✓ Created {$created} new tags");
        $this->command->info("✅ Total tags: {$total}");
    }
}
