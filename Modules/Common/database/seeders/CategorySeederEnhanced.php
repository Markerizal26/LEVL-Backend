<?php

declare(strict_types=1);

namespace Modules\Common\Database\Seeders;

use Bezhanov\Faker\Provider\Educator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Common\Models\Category;

class CategorySeederEnhanced extends Seeder
{
    public function run(): void
    {
        $this->command->info("\n📂 Creating realistic categories...");

        fake()->addProvider(new Educator(fake()));

        $categories = [
            // Technology & IT
            ['name' => 'Web Development', 'value' => 'web-development', 'description' => 'Full-stack web development, frontend, backend'],
            ['name' => 'Mobile Development', 'value' => 'mobile-development', 'description' => 'iOS, Android, React Native, Flutter development'],
            ['name' => 'Data Science', 'value' => 'data-science', 'description' => 'Machine learning, AI, data analysis, big data'],
            ['name' => 'DevOps & Cloud', 'value' => 'devops-cloud', 'description' => 'CI/CD, Docker, Kubernetes, AWS, Azure, GCP'],
            ['name' => 'Cybersecurity', 'value' => 'cybersecurity', 'description' => 'Network security, ethical hacking, penetration testing'],
            ['name' => 'Database Administration', 'value' => 'database-admin', 'description' => 'SQL, NoSQL, PostgreSQL, MongoDB, Redis'],
            
            // Business & Management
            ['name' => 'Project Management', 'value' => 'project-management', 'description' => 'Agile, Scrum, PMP, project planning'],
            ['name' => 'Business Analytics', 'value' => 'business-analytics', 'description' => 'Business intelligence, reporting, dashboards'],
            ['name' => 'Entrepreneurship', 'value' => 'entrepreneurship', 'description' => 'Startup, business planning, fundraising'],
            ['name' => 'Leadership & Management', 'value' => 'leadership-management', 'description' => 'Team management, organizational behavior'],
            
            // Design & Creative
            ['name' => 'UI/UX Design', 'value' => 'ui-ux-design', 'description' => 'User interface, user experience, design thinking'],
            ['name' => 'Graphic Design', 'value' => 'graphic-design', 'description' => 'Photoshop, Illustrator, branding, typography'],
            ['name' => '3D Modeling & Animation', 'value' => '3d-modeling', 'description' => 'Blender, Maya, 3D rendering, animation'],
            ['name' => 'Video Production', 'value' => 'video-production', 'description' => 'Video editing, cinematography, motion graphics'],
            
            // Marketing & Sales
            ['name' => 'Digital Marketing', 'value' => 'digital-marketing', 'description' => 'SEO, SEM, social media marketing, content marketing'],
            ['name' => 'Social Media Marketing', 'value' => 'social-media-marketing', 'description' => 'Instagram, Facebook, TikTok, LinkedIn marketing'],
            ['name' => 'Email Marketing', 'value' => 'email-marketing', 'description' => 'Email campaigns, automation, newsletters'],
            ['name' => 'Sales & Negotiation', 'value' => 'sales-negotiation', 'description' => 'Sales techniques, closing deals, B2B/B2C sales'],
            
            // Finance & Accounting
            ['name' => 'Financial Analysis', 'value' => 'financial-analysis', 'description' => 'Financial modeling, valuation, investment analysis'],
            ['name' => 'Accounting', 'value' => 'accounting', 'description' => 'Bookkeeping, tax, financial statements'],
            ['name' => 'Personal Finance', 'value' => 'personal-finance', 'description' => 'Budgeting, investing, retirement planning'],
            
            // Languages
            ['name' => 'English Language', 'value' => 'english-language', 'description' => 'Business English, TOEFL, IELTS preparation'],
            ['name' => 'Programming Languages', 'value' => 'programming-languages', 'description' => 'Python, Java, JavaScript, C++, Go, Rust'],
            
            // Soft Skills
            ['name' => 'Communication Skills', 'value' => 'communication-skills', 'description' => 'Public speaking, presentation, writing'],
            ['name' => 'Productivity & Time Management', 'value' => 'productivity', 'description' => 'Task management, focus, efficiency techniques'],
            ['name' => 'Career Development', 'value' => 'career-development', 'description' => 'Resume writing, interview skills, networking'],
        ];

        $created = 0;
        $categoryData = [];

        foreach ($categories as $cat) {
            if (!Category::where('value', $cat['value'])->exists()) {
                $categoryData[] = [
                    'name' => $cat['name'],
                    'value' => $cat['value'],
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $created++;
            }
        }

        if (!empty($categoryData)) {
            DB::table('categories')->insert($categoryData);
        }

        $total = Category::count();
        $this->command->info("  ✓ Created {$created} new categories");
        $this->command->info("✅ Total categories: {$total}");
    }
}
