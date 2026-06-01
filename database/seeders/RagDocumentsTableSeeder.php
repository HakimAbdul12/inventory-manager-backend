<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RagDocumentsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('rag_documents')->delete();
        
        \DB::table('rag_documents')->insert(array (
            0 => 
            array (
                'id' => '019d868a-a9a1-7043-8422-31a052791043',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'page_id' => '019ce94f-6dad-719f-b3d7-417f2d572107',
                'content' => 'About Our Mission Beyond the filler text, we aim to provide a canvas for your imagination. SleekDummy was founded on the principle that even dummy websites deserve to look stunning. We don\'t just provide "Lorem Ipsum"; we provide an experience. Our team of artificial intelligences worked tirelessly to ensure this pinker-than-average aesthetic meets the highest standards of the modern web. Every gradient is calculated, every blur is intentional. The Vision To redefine what users expect from a placeholder project. The Value Simplicity meets sophistication in every line of code.',
                'chunk_index' => 0,
                'metadata_json' => '{"url":"http:\\/\\/localhost:4500\\/about.html","title":"SleekDummy | About Us","crawl_job_id":"019ce94f-6857-73ea-bd80-e2ce9ded4bff","chunk_index":0}',
                'vectorized' => false,
                'created_at' => '2026-04-13 11:12:07',
                'updated_at' => '2026-04-13 11:12:07',
            ),
            1 => 
            array (
                'id' => '019d868a-a9a4-7150-876b-cb3cbd04e41b',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'page_id' => '019ce94f-6dc0-7357-a978-6628dd0c0e42',
            'content' => 'Get in Touch Have questions? Our dummy support team is waiting (to not exist). Full Name Email Address Your Message Send Message',
                'chunk_index' => 0,
                'metadata_json' => '{"url":"http:\\/\\/localhost:4500\\/contact.html","title":"SleekDummy | Contact","crawl_job_id":"019ce94f-6857-73ea-bd80-e2ce9ded4bff","chunk_index":0}',
                'vectorized' => false,
                'created_at' => '2026-04-13 11:12:07',
                'updated_at' => '2026-04-13 11:12:07',
            ),
            2 => 
            array (
                'id' => '019d868a-a9a6-71da-ab14-5c101227671f',
                'crawl_job_id' => '019ce94f-6857-73ea-bd80-e2ce9ded4bff',
                'page_id' => '019ce94f-6dd0-7104-a14e-5bfe62836865',
                'content' => 'Terms & Conditions The fine print that explains why nothing here is real. 1. Acceptance of Dummy Terms By accessing this website, you agree that you are viewing a demonstration project. No actual services, products, or real-world entities are represented here. 2. Use of Placeholder Data All images and text are for illustrative purposes only. Any resemblance to real life is purely coincidental and frankly, quite surprising. 3. Limitation of Liability We are not liable for any time spent admiring the sleek design or the inevitable disappointment when you realize the form doesn\'t actually send an email. 4. Intellectual Property The aesthetics of this dummy site are the property of the Antigravity AI Project. Feel free to be inspired by the CSS.',
                'chunk_index' => 0,
                'metadata_json' => '{"url":"http:\\/\\/localhost:4500\\/terms.html","title":"SleekDummy | Terms","crawl_job_id":"019ce94f-6857-73ea-bd80-e2ce9ded4bff","chunk_index":0}',
                'vectorized' => false,
                'created_at' => '2026-04-13 11:12:07',
                'updated_at' => '2026-04-13 11:12:07',
            ),
        ));
        
        
    }
}