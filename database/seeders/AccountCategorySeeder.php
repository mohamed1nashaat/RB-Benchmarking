<?php

namespace Database\Seeders;

use App\Models\AccountCategory;
use App\Models\AccountSubCategory;
use App\Models\Industry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountCategorySeeder extends Seeder
{
    /**
     * Industry → Account Category → Sub-Category mapping.
     * Each sub-category list is curated to be semantically relevant to the parent category.
     */
    private array $hierarchy = [
        'Automotive' => [
            'Vehicles' => ['New Cars', 'Used Cars', 'Luxury Vehicles', 'SUVs & Trucks', 'Commercial Vehicles', 'Passenger Cars'],
            'Auto Dealers & Retail' => ['Franchise Dealers', 'Independent Dealers', 'Online Auto Retailers', 'Used Car Lots', 'Certified Pre-Owned'],
            'Auto Parts & Services' => ['OEM Parts', 'Aftermarket Parts', 'Tires & Batteries', 'Maintenance & Repair', 'Body Shops', 'Oil & Fluids'],
            'Electric Vehicles' => ['EV Brands', 'Charging Infrastructure', 'EV Financing', 'Fleet Electrification', 'Battery Technology', 'EV Leasing'],
            'Rentals & Leasing' => ['Daily Rentals', 'Long-Term Leasing', 'Corporate Fleet', 'Peer-to-Peer Rentals', 'Luxury Rentals'],
            'Motorsports' => ['Formula Racing', 'Rally & Off-Road', 'Motorbike Racing', ' Karting', 'Esports Racing'],
        ],
        'Travel & Destinations' => [
            'Tourism Boards' => ['Destination Marketing', 'Visitor Information', 'Cultural Promotion', 'Seasonal Campaigns', 'International Outreach'],
            'Airlines' => ['Full-Service Carriers', 'Low-Cost Carriers', 'Regional Airlines', 'Cargo Airlines', 'Private Jets'],
            'Hotels & Resorts' => ['Luxury Hotels', 'Business Hotels', 'Resorts', 'Boutique Hotels', 'Budget Hotels', 'Serviced Apartments'],
            'Cruises' => ['Ocean Cruises', 'River Cruises', 'Luxury Cruises', 'Family Cruises', 'Expedition Cruises'],
            'Online Travel Agencies' => ['Flight Booking', 'Hotel Booking', 'Package Deals', 'Car Hire', 'Travel Insurance'],
            'Local Attractions & Experiences' => ['Museums', 'Adventure Parks', 'Cultural Sites', 'Entertainment', 'Guided Tours', 'Wildlife Experiences'],
        ],
        'E-commerce / Retail' => [
            'Fashion & Apparel' => ["Women's Fashion", "Men's Fashion", "Children's Fashion", 'Accessories', 'Luxury Fashion', 'Athleisure'],
            'Electronics' => ['Smartphones', 'Laptops & PCs', 'Audio & Headphones', 'Cameras', 'Smart Home', 'Gaming Hardware'],
            'Home & Furniture' => ['Living Room', 'Bedroom', 'Kitchen & Dining', 'Office Furniture', 'Outdoor', 'Home Decor'],
            'Beauty & Cosmetics' => ['Skincare', 'Makeup', 'Fragrances', 'Haircare', 'Personal Grooming', 'Natural & Organic'],
            'Grocery & Food Delivery' => ['Fresh Produce', 'Packaged Foods', 'Beverages', 'Ready Meals', 'Restaurant Delivery', 'Grocery Apps'],
            'Marketplaces' => ['General Marketplaces', 'Niche Marketplaces', 'B2B Marketplaces', 'Auction Sites', 'Second-Hand Platforms'],
            'Direct-to-Consumer (D2C)' => ['D2C Brands', 'Subscription Boxes', 'Personal Care D2C', 'Food & Drink D2C', 'Home Goods D2C'],
        ],
        'Education' => [
            'K–12 Schools' => ['Private Schools', 'International Schools', 'Public Schools', 'Charter Schools', 'Online K–12', 'Special Education'],
            'Universities & Colleges' => ['Public Universities', 'Private Universities', 'Community Colleges', 'Business Schools', 'Medical Schools', 'Liberal Arts Colleges'],
            'Online Courses / EdTech' => ['MOOCs', 'Coding Bootcamps', 'Language Learning', 'Corporate Learning', 'K–12 EdTech', 'Test Prep Apps'],
            'Training & Certification Centers' => ['Corporate Training', 'Professional Certification', 'Vocational Training', 'Leadership Development', 'Safety Training'],
            'Tutoring & Test Prep' => ['Private Tutoring', 'Group Classes', 'SAT / ACT Prep', 'Language Tutoring', 'STEM Tutoring'],
        ],
        'FMCG / CPG' => [
            'Food & Beverages' => ['Snacks & Confectionery', 'Dairy & Alternatives', 'Beverages', 'Frozen Foods', 'Bakery', 'Baby Food'],
            'Personal Care' => ['Body Care', 'Haircare', 'Wellness', 'Hygiene Products', 'Oral Care', 'Baby Care'],
            'Household Products' => ['Laundry Care', 'Surface Cleaners', 'Air Fresheners', 'Paper Products', 'Insect Control'],
            'Cleaning & Hygiene' => ['Disinfectants', 'Hand Hygiene', 'Industrial Cleaning', 'Sanitary Supplies', 'Wipes'],
            'Pet Care' => ['Pet Food', 'Pet Treats', 'Pet Health', 'Pet Grooming', 'Pet Accessories'],
            'Tobacco & Alcohol' => ['Cigarettes', 'Vaping', 'Beer', 'Wine & Spirits', 'Low/No Alcohol'],
        ],
        'Real Estate' => [
            'Residential Projects' => ['Apartments', 'Villas', 'Townhouses', 'Mixed-Use Developments', 'Affordable Housing', 'Gated Communities'],
            'Commercial Developments' => ['Office Buildings', 'Retail Spaces', 'Warehouses', 'Co-Working Spaces', 'Industrial Parks'],
            'Real Estate Agencies' => ['Residential Agencies', 'Commercial Agencies', 'Property Management', 'Relocation Services', 'Luxury Brokerages'],
            'Property Portals' => ['Rental Listings', 'Sales Listings', 'Mortgage Comparison', 'Virtual Tours', 'Property Valuation'],
            'Construction & Contracting' => ['General Contracting', 'Interior Fit-Out', 'MEP Contracting', 'Renovation', 'Project Management'],
        ],
        'Finance & Insurance' => [
            'Banking' => ['Retail Banking', 'Corporate Banking', 'Digital Banking', 'Islamic Banking', 'Private Banking', 'SME Banking'],
            'Fintech' => ['Payments', 'Neobanking', 'Lending Platforms', 'WealthTech', 'InsurTech', 'Blockchain & Crypto'],
            'Investments & Wealth Management' => ['Asset Management', 'Private Equity', 'Wealth Advisory', 'Brokerage', 'Hedge Funds', 'Family Offices'],
            'Insurance' => ['Life Insurance', 'Health Insurance', 'Motor Insurance', 'Property Insurance', 'Travel Insurance', 'Commercial Insurance'],
            'Credit & Loans' => ['Personal Loans', 'Mortgages', 'Auto Loans', 'SME Loans', 'Credit Cards', 'Buy Now Pay Later'],
        ],
        'Healthcare & Pharmaceuticals' => [
            'Hospitals & Clinics' => ['General Hospitals', 'Specialty Clinics', 'Dental Clinics', 'Diagnostic Centers', 'Day Surgery Centers'],
            'Health Insurance' => ['Individual Plans', 'Group Plans', 'Travel Health', 'Critical Illness', 'Dental & Vision'],
            'Pharmaceuticals' => ['Prescription Drugs', 'OTC Medicines', 'Vaccines', 'Biotech', 'Generics', 'Rare Diseases'],
            'Medical Devices' => ['Diagnostic Devices', 'Surgical Equipment', 'Wearables', 'Imaging Systems', 'Orthopedic Devices'],
            'Wellness & Nutrition' => ['Vitamins & Supplements', 'Sports Nutrition', 'Weight Management', 'Wellness Apps', 'Spa & Wellness Centers'],
            'Telemedicine' => ['Virtual Consultations', 'Remote Monitoring', 'Mental Health Apps', 'Online Prescriptions', 'Chronic Care Management'],
        ],
        'Technology' => [
            'Software / SaaS' => ['B2B Software', 'Cloud Services', 'Productivity Tools', 'Security Software', 'CRM & ERP', 'Collaboration Tools'],
            'Hardware & Devices' => ['Servers & Storage', 'Networking Equipment', 'Peripherals', 'Semiconductors', 'Components'],
            'IT Services' => ['Managed IT', 'Cloud Consulting', 'Cybersecurity Services', 'System Integration', 'IT Support'],
            'Telecommunications' => ['Mobile Carriers', 'Internet Service Providers', 'Communication Apps', 'Smart Devices', 'VoIP Services'],
            'Gaming & Esports' => ['Game Publishers', 'Esports Teams', 'Streaming Platforms', 'Gaming Hardware', 'Game Development'],
            'AI & Emerging Tech' => ['Generative AI', 'Machine Learning Platforms', 'Robotics', 'IoT Solutions', 'AR/VR'],
        ],
        'Entertainment & Media' => [
            'Streaming Services' => ['Video Streaming', 'Music Streaming', 'Live Streaming', 'Podcast Platforms', 'Sports Streaming'],
            'Sports & Events' => ['Professional Leagues', 'Event Management', 'Ticketing', 'Venues & Stadiums', 'Esports Events'],
            'Music & Film' => ['Record Labels', 'Film Studios', 'Distribution', 'Concerts & Tours', 'Music Festivals'],
            'News & Publishing' => ['Digital News', 'Print Media', 'Magazines', 'Broadcast News', 'News Aggregators'],
            'Influencers & Creators' => ['Talent Agencies', 'Creator Platforms', 'Brand Collaborations', 'UGC Marketplaces', 'Podcasters'],
            'Gaming & Esports' => ['Game Publishers', 'Esports Teams', 'Streaming Platforms', 'Gaming Hardware', 'Game Development'],
        ],
        'Government & Public Sector' => [
            'Ministries & Authorities' => ['Government Services', 'Public Administration', 'Regulatory Bodies', 'Municipal Services'],
            'Public Awareness Campaigns' => ['Health Awareness', 'Safety Campaigns', 'Environmental Awareness', 'Civic Engagement', 'Road Safety'],
            'NGOs & Foundations' => ['Humanitarian NGOs', 'Development Foundations', 'Advocacy Groups', 'Research Foundations'],
            'Vision & Development Programs' => ['National Vision', 'Economic Diversification', 'Smart City Initiatives', 'Youth Programs', 'Sustainability Programs'],
        ],
        'Hospitality & Food Services' => [
            'Restaurants & Cafes' => ['Fine Dining', 'Casual Dining', 'Fast Food', 'Cafes', 'Cloud Kitchens', 'Franchises'],
            'Hotels & Resorts' => ['Luxury Hotels', 'Business Hotels', 'Resorts', 'Boutique Hotels', 'Budget Hotels'],
            'Catering & Events' => ['Wedding Catering', 'Corporate Catering', 'Event Catering', 'Food Trucks', 'Banquet Halls'],
            'Delivery Apps / Cloud Kitchens' => ['Food Delivery Apps', 'Cloud Kitchen Brands', 'Dark Stores', 'Quick Commerce', 'Grocery Delivery'],
        ],
        'Energy & Utilities' => [
            'Oil & Gas' => ['Upstream', 'Midstream', 'Downstream', 'LNG', 'Petrochemicals', 'Oilfield Services'],
            'Renewable Energy' => ['Solar', 'Wind', 'Hydropower', 'Green Hydrogen', 'Energy Storage'],
            'Power & Electricity' => ['Power Generation', 'Transmission & Distribution', 'Utilities', 'Grid Modernization', 'Nuclear'],
            'Water & Waste Management' => ['Water Treatment', 'Waste Collection', 'Recycling', 'Wastewater Management'],
            'Environmental Services' => ['Emissions Management', 'Environmental Consulting', 'Carbon Credits', 'Sustainability Solutions'],
        ],
        'Construction & Manufacturing' => [
            'Construction Firms' => ['General Contractors', 'Specialty Contractors', 'Infrastructure', 'Residential Construction', 'Commercial Construction'],
            'Industrial Manufacturing' => ['Heavy Industry', 'Consumer Goods Manufacturing', 'Electronics Manufacturing', 'Textile Manufacturing', 'Automotive Manufacturing'],
            'Building Materials' => ['Cement & Concrete', 'Steel & Metals', 'Wood & Timber', 'Glass & Facades', 'Insulation & Finishes'],
            'Engineering & Contracting' => ['Civil Engineering', 'Mechanical Engineering', 'Electrical Engineering', 'EPC Contractors', 'Design-Build'],
            'Heavy Equipment' => ['Earthmoving Equipment', 'Cranes & Lifting', 'Mining Equipment', 'Agricultural Machinery', 'Rental Fleets'],
        ],
        'Fashion & Luxury' => [
            'Luxury Goods' => ['Luxury Fashion', 'Fine Jewelry', 'High-End Watches', 'Leather Goods', 'Luxury Accessories'],
            'Jewelry & Watches' => ['Fine Jewelry', 'Fashion Jewelry', 'Swiss Watches', 'Smart Watches', 'Custom Jewelry'],
            'Designer Apparel' => ['Haute Couture', 'Ready-to-Wear', 'Designer Menswear', 'Designer Kidswear', 'Bridal'],
            'Footwear & Accessories' => ['Luxury Footwear', 'Sneakers', 'Handbags', 'Belts & Wallets', 'Eyewear'],
        ],
        'Telecommunications' => [
            'Mobile Carriers' => ['Postpaid Plans', 'Prepaid Plans', '5G Services', 'Mobile Money', 'Enterprise Mobility'],
            'Internet Service Providers' => ['Fiber Broadband', 'DSL', 'Satellite Internet', 'Enterprise Internet', 'Public WiFi'],
            'Communication Apps' => ['Messaging Apps', 'Video Conferencing', 'Social Media', 'Email Services', 'Collaboration Tools'],
            'Smart Devices' => ['Smartphones', 'Tablets', 'Smartwatches', 'IoT Devices', 'Home Automation'],
        ],
        'Transportation & Logistics' => [
            'Shipping & Freight' => ['Sea Freight', 'Air Freight', 'Land Freight', 'Cross-Border Shipping', 'Freight Forwarding'],
            'Courier & Delivery' => ['Last-Mile Delivery', 'Same-Day Delivery', 'International Courier', 'E-Commerce Logistics', 'Cold Chain'],
            'Ports & Aviation Logistics' => ['Port Operations', 'Air Cargo', 'Free Zones', 'Customs Brokerage', 'Warehousing'],
            'Railways & Metro Systems' => ['Passenger Rail', 'Freight Rail', 'Metro Systems', 'High-Speed Rail', 'Rail Infrastructure'],
        ],
        'Nonprofit & Social Impact' => [
            'Charities' => ['Humanitarian Aid', 'Child Welfare', 'Poverty Alleviation', 'Disaster Relief', 'Community Development'],
            'Fundraising Platforms' => ['Crowdfunding', 'Donation Platforms', 'Charity Auctions', 'Peer-to-Peer Fundraising', 'Corporate Giving'],
            'Awareness Campaigns' => ['Health Awareness', 'Education Awareness', 'Human Rights', 'Climate Awareness', 'Mental Health'],
            'Environmental & Humanitarian Causes' => ['Wildlife Conservation', 'Refugee Support', 'Clean Water', 'Reforestation', 'Ocean Conservation'],
        ],
        'Agriculture & Food Production' => [
            'Farming & Agritech' => ['Crop Farming', 'Livestock Farming', 'Precision Agriculture', 'Vertical Farming', 'Irrigation Technology'],
            'Food Manufacturing' => ['Dairy Processing', 'Beverage Production', 'Bakery Manufacturing', 'Meat Processing', 'Packaged Foods'],
            'Fisheries & Livestock' => ['Aquaculture', 'Commercial Fishing', 'Poultry', 'Dairy Livestock', 'Animal Feed'],
            'Agricultural Equipment' => ['Tractors & Harvesters', 'Irrigation Systems', 'Drones & Sensors', 'Greenhouse Equipment', 'Storage & Handling'],
        ],
        'Professional Services' => [
            'Consulting' => ['Management Consulting', 'IT Consulting', 'Financial Advisory', 'HR Consulting', 'Strategy Consulting'],
            'Marketing & Advertising' => ['Digital Marketing', 'Brand Strategy', 'Creative Agency', 'Media Buying', 'SEO / SEM', 'Influencer Marketing'],
        ],
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('account_sub_categories')->truncate();
        DB::table('account_categories')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Load all industries keyed by display name for quick lookup
        $industries = Industry::all()->keyBy('display_name');

        $totalCategories = 0;
        $totalSubCategories = 0;
        $usedCategoryNames = [];

        foreach ($this->hierarchy as $industryDisplayName => $categories) {
            $industry = $industries->get($industryDisplayName);

            if (! $industry) {
                $this->command->warn("Industry not found: {$industryDisplayName}");
                continue;
            }

            $categorySort = 0;
            foreach ($categories as $categoryDisplayName => $subCategories) {
                $baseName = Str::slug($categoryDisplayName, '_');
                $name = $baseName;
                $counter = 1;
                while (isset($usedCategoryNames[$name])) {
                    $name = $baseName . '_' . ++$counter;
                }
                $usedCategoryNames[$name] = true;

                $category = AccountCategory::create([
                    'industry_id' => $industry->id,
                    'name' => $name,
                    'display_name' => $categoryDisplayName,
                    'description' => null,
                    'is_active' => true,
                    'sort_order' => $categorySort++,
                ]);
                $totalCategories++;

                foreach ($subCategories as $subSort => $subDisplayName) {
                    AccountSubCategory::create([
                        'account_category_id' => $category->id,
                        'name' => Str::slug($subDisplayName, '_'),
                        'display_name' => $subDisplayName,
                        'description' => null,
                        'is_active' => true,
                        'sort_order' => $subSort,
                    ]);
                    $totalSubCategories++;
                }
            }
        }

        $this->command->info("Seeded {$totalCategories} account categories with {$totalSubCategories} sub-categories.");
    }
}
