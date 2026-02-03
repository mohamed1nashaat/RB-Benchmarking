<template>
  <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-medium text-gray-900">{{ $t('filters.benchmark_filters') }}</h3>
      <button
        @click="clearAllFilters"
        class="text-sm text-gray-500 hover:text-gray-700"
      >
        {{ $t('filters.clear_all') }}
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
      <!-- Platform Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('labels.platform') }}
        </label>
        <select
          v-model="filters.platform"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('filters.all_platforms') }}</option>
          <option v-for="(label, value) in platformLabels" :key="value" :value="value">
            {{ label }}
          </option>
        </select>
      </div>

      <!-- Objective Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('filters.campaign_objective') }}
        </label>
        <select
          v-model="filters.objective"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('filters.all_objectives') }}</option>
          <option v-for="objective in objectives" :key="objective" :value="objective">
            {{ formatObjective(objective) }}
          </option>
        </select>
      </div>

      <!-- Funnel Stage Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('funnel_stages.label') }}
        </label>
        <select
          v-model="filters.funnel_stage"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('funnel_stages.all') }}</option>
          <option v-for="(label, value) in funnelStageLabels" :key="value" :value="value">
            {{ label }}
          </option>
        </select>
      </div>

      <!-- User Journey Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('user_journeys.label') }}
        </label>
        <select
          v-model="filters.user_journey"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('user_journeys.all') }}</option>
          <option v-for="(label, value) in userJourneyLabels" :key="value" :value="value">
            {{ label }}
          </option>
        </select>
      </div>

      <!-- Industry Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('labels.industry') }}
        </label>
        <select
          v-model="filters.industry"
          @change="onIndustryChange"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('filters.all_industries') }}</option>
          <option v-for="industry in industries" :key="industry.name" :value="industry.name">
            {{ industry.display_name }}
          </option>
        </select>
      </div>

      <!-- Sub-Industry Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('filters.sub_industry') }}
        </label>
        <select
          v-model="filters.sub_industry"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('filters.all_sub_industries') }}</option>
          <option v-for="subIndustry in subIndustries" :key="subIndustry.name" :value="subIndustry.name">
            {{ subIndustry.display_name }}
          </option>
        </select>
      </div>

      <!-- Pixel Data Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('pixel_data.label') }}
        </label>
        <select
          v-model="filters.has_pixel_data"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('pixel_data.all_campaigns') }}</option>
          <option value="true">{{ $t('pixel_data.with_pixel') }}</option>
          <option value="false">{{ $t('pixel_data.without_pixel') }}</option>
        </select>
      </div>

      <!-- Target Segment Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('target_segments.label') }}
        </label>
        <select
          v-model="filters.target_segment"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('target_segments.all') }}</option>
          <option value="luxury">{{ $t('target_segments.luxury') }}</option>
          <option value="premium">{{ $t('target_segments.premium') }}</option>
          <option value="mid_class">{{ $t('target_segments.mid_class') }}</option>
          <option value="value">{{ $t('target_segments.value_budget') }}</option>
          <option value="mass_market">{{ $t('target_segments.mass_market') }}</option>
          <option value="niche">{{ $t('target_segments.niche') }}</option>
        </select>
      </div>

      <!-- Age Group Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('age_groups.label') }}
        </label>
        <select
          v-model="filters.age_group"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('age_groups.all') }}</option>
          <option value="gen_z">{{ $t('age_groups.gen_z') }}</option>
          <option value="millennials">{{ $t('age_groups.millennials') }}</option>
          <option value="gen_x">{{ $t('age_groups.gen_x') }}</option>
          <option value="boomers">{{ $t('age_groups.boomers') }}</option>
          <option value="mixed_age">{{ $t('age_groups.mixed_age') }}</option>
        </select>
      </div>

      <!-- Geo Targeting Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('geo_targeting.label') }}
        </label>
        <select
          v-model="filters.geo_targeting"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('geo_targeting.all') }}</option>
          <option value="local">{{ $t('geo_targeting.local') }}</option>
          <option value="regional">{{ $t('geo_targeting.regional') }}</option>
          <option value="national">{{ $t('geo_targeting.national') }}</option>
          <option value="international">{{ $t('geo_targeting.international') }}</option>
        </select>
      </div>

      <!-- Messaging Tone Filter -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('messaging_tone.label') }}
        </label>
        <select
          v-model="filters.messaging_tone"
          @change="updateFilters"
          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="">{{ $t('messaging_tone.all') }}</option>
          <option value="professional">{{ $t('messaging_tone.professional') }}</option>
          <option value="casual">{{ $t('messaging_tone.casual') }}</option>
          <option value="luxury">{{ $t('messaging_tone.luxury') }}</option>
          <option value="urgent">{{ $t('messaging_tone.urgent') }}</option>
          <option value="educational">{{ $t('messaging_tone.educational') }}</option>
          <option value="emotional">{{ $t('messaging_tone.emotional') }}</option>
        </select>
      </div>

      <!-- Date Range (if not handled elsewhere) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
          {{ $t('dashboard.date_range') }}
        </label>
        <DateRangePicker @change="onDateRangeChange" :value="dateRange" />
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from 'vue'
import DateRangePicker from '@/components/DateRangePicker.vue'

const emit = defineEmits(['filtersChanged'])

const props = defineProps<{
  initialFilters?: any
  dateRange?: any
}>()

// Filter data
const filters = ref({
  platform: '',
  objective: '',
  funnel_stage: '',
  user_journey: '',
  industry: '',
  sub_industry: '',
  has_pixel_data: '',
  target_segment: '',
  age_group: '',
  geo_targeting: '',
  messaging_tone: ''
})

// Options data
const platformLabels = ref({})
const funnelStageLabels = ref({})
const userJourneyLabels = ref({})
const objectives = ref([])
const industries = ref([])
const subIndustries = ref([])

// Modal state

// Load filter options
onMounted(async () => {
  console.log('Loading filter options...')

  // Use static data since demo endpoints don't exist
  platformLabels.value = {
    facebook: 'Facebook',
    instagram: 'Instagram',
    google: 'Google Ads',
    youtube: 'YouTube',
    linkedin: 'LinkedIn',
    twitter: 'Twitter',
    tiktok: 'TikTok'
  }

  funnelStageLabels.value = {
    awareness: 'Awareness',
    consideration: 'Consideration',
    conversion: 'Conversion',
    retention: 'Retention'
  }

  userJourneyLabels.value = {
    discovery: 'Discovery',
    research: 'Research',
    evaluation: 'Evaluation',
    purchase: 'Purchase',
    advocacy: 'Advocacy'
  }

  objectives.value = [
    'awareness',
    'consideration',
    'conversion',
    'retention',
    'advocacy',
    'leads',
    'sales',
    'traffic',
    'engagement',
    'app_installs'
  ]

  // Load industries from API
  await loadIndustries()

  // Load initial sub-industries
  loadSubIndustries()

  // Set initial filters if provided
  if (props.initialFilters) {
    Object.assign(filters.value, props.initialFilters)
  }

  console.log('Filter options loaded:', {
    platforms: Object.keys(platformLabels.value).length,
    industries: industries.value.length,
    objectives: objectives.value.length
  })
})

// Load industries from API
const loadIndustries = async () => {
  try {
    const response = await fetch('/api/industries', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      }
    })

    if (response.ok) {
      const data = await response.json()
      industries.value = data.data.map((industry: any) => ({
        name: industry.name,
        display_name: industry.display_name
      }))
    } else {
      console.warn('Failed to load industries from API, using fallback')
      // Fallback to static data
      industries.value = [
        { name: 'automotive', display_name: 'Automotive' },
        { name: 'beauty_fitness', display_name: 'Beauty & Fitness' },
        { name: 'business_industrial', display_name: 'Business & Industrial' },
        { name: 'computers_electronics', display_name: 'Computers & Electronics' },
        { name: 'education', display_name: 'Education' },
        { name: 'entertainment', display_name: 'Entertainment' },
        { name: 'finance_insurance', display_name: 'Finance & Insurance' },
        { name: 'food_beverage', display_name: 'Food & Beverage' },
        { name: 'health_medicine', display_name: 'Health & Medicine' },
        { name: 'home_garden', display_name: 'Home & Garden' },
        { name: 'law_government', display_name: 'Law & Government' },
        { name: 'lifestyle', display_name: 'Lifestyle' },
        { name: 'media_publishing', display_name: 'Media & Publishing' },
        { name: 'nonprofit', display_name: 'Nonprofit' },
        { name: 'real_estate', display_name: 'Real Estate' },
        { name: 'retail_ecommerce', display_name: 'Retail & E-commerce' },
        { name: 'sports_recreation', display_name: 'Sports & Recreation' },
        { name: 'technology', display_name: 'Technology' },
        { name: 'travel_tourism', display_name: 'Travel & Tourism' },
        { name: 'other', display_name: 'Other' }
      ]
    }
  } catch (error) {
    console.error('Error loading industries:', error)
    // Fallback to static data
    industries.value = [
      { name: 'automotive', display_name: 'Automotive' },
      { name: 'beauty_fitness', display_name: 'Beauty & Fitness' },
      { name: 'business_industrial', display_name: 'Business & Industrial' },
      { name: 'computers_electronics', display_name: 'Computers & Electronics' },
      { name: 'education', display_name: 'Education' },
      { name: 'entertainment', display_name: 'Entertainment' },
      { name: 'finance_insurance', display_name: 'Finance & Insurance' },
      { name: 'food_beverage', display_name: 'Food & Beverage' },
      { name: 'health_medicine', display_name: 'Health & Medicine' },
      { name: 'home_garden', display_name: 'Home & Garden' },
      { name: 'law_government', display_name: 'Law & Government' },
      { name: 'lifestyle', display_name: 'Lifestyle' },
      { name: 'media_publishing', display_name: 'Media & Publishing' },
      { name: 'nonprofit', display_name: 'Nonprofit' },
      { name: 'real_estate', display_name: 'Real Estate' },
      { name: 'retail_ecommerce', display_name: 'Retail & E-commerce' },
      { name: 'sports_recreation', display_name: 'Sports & Recreation' },
      { name: 'technology', display_name: 'Technology' },
      { name: 'travel_tourism', display_name: 'Travel & Tourism' },
      { name: 'other', display_name: 'Other' }
    ]
  }
}

// Load sub-industries based on selected industry
const loadSubIndustries = async () => {
  if (!filters.value.industry) {
    subIndustries.value = []
    return
  }

  try {
    // Find the industry ID from the loaded industries
    const industry = industries.value.find((ind: any) => ind.name === filters.value.industry)
    if (!industry) {
      console.warn('Selected industry not found in loaded industries')
      subIndustries.value = []
      return
    }

    const response = await fetch(`/api/industries/${filters.value.industry}/sub-industries`, {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
      }
    })

    if (response.ok) {
      const data = await response.json()
      subIndustries.value = data.data.map((subIndustry: any) => ({
        name: subIndustry.name,
        display_name: subIndustry.display_name
      }))
    } else {
      console.warn('Failed to load sub-industries from API, using fallback')
      // Fallback to static sub-industries based on selected industry
      const industrySubIndustries: Record<string, any[]> = {
        technology: [
          { name: 'software_development', display_name: 'Software Development' },
          { name: 'saas', display_name: 'SaaS' },
          { name: 'mobile_apps', display_name: 'Mobile Apps' },
          { name: 'cybersecurity', display_name: 'Cybersecurity' }
        ],
        real_estate: [
          { name: 'residential_sales', display_name: 'Residential Sales' },
          { name: 'commercial_real_estate', display_name: 'Commercial Real Estate' },
          { name: 'property_management', display_name: 'Property Management' },
          { name: 'luxury_properties', display_name: 'Luxury Properties' }
        ],
        retail_ecommerce: [
          { name: 'fashion_apparel', display_name: 'Fashion & Apparel' },
          { name: 'electronics', display_name: 'Electronics' },
          { name: 'home_goods', display_name: 'Home Goods' },
          { name: 'beauty_products', display_name: 'Beauty Products' }
        ],
        health_medicine: [
          { name: 'dental', display_name: 'Dental' },
          { name: 'medical_practice', display_name: 'Medical Practice' },
          { name: 'wellness', display_name: 'Wellness' },
          { name: 'mental_health', display_name: 'Mental Health' }
        ],
        finance_insurance: [
          { name: 'banking', display_name: 'Banking' },
          { name: 'investment', display_name: 'Investment' },
          { name: 'insurance', display_name: 'Insurance' },
          { name: 'fintech', display_name: 'Fintech' }
        ],
        automotive: [
          { name: 'car_sales', display_name: 'Car Sales' },
          { name: 'auto_parts', display_name: 'Auto Parts' },
          { name: 'car_services', display_name: 'Car Services' },
          { name: 'electric_vehicles', display_name: 'Electric Vehicles' }
        ],
        education: [
          { name: 'online_learning', display_name: 'Online Learning' },
          { name: 'universities', display_name: 'Universities' },
          { name: 'k12_schools', display_name: 'K-12 Schools' },
          { name: 'training_centers', display_name: 'Training Centers' }
        ]
      }

      subIndustries.value = industrySubIndustries[filters.value.industry] || []
    }
  } catch (error) {
    console.error('Error loading sub-industries:', error)
    subIndustries.value = []
  }
}

// Handle industry change
const onIndustryChange = async () => {
  filters.value.sub_industry = '' // Clear sub-industry when industry changes
  await loadSubIndustries()
  updateFilters()
}

// Update filters
const updateFilters = () => {
  emit('filtersChanged', { ...filters.value })
}

// Clear all filters
const clearAllFilters = () => {
  filters.value = {
    platform: '',
    objective: '',
    funnel_stage: '',
    user_journey: '',
    industry: '',
    sub_industry: '',
    has_pixel_data: '',
    target_segment: '',
    age_group: '',
    geo_targeting: '',
    messaging_tone: ''
  }
  updateFilters()
}

// Handle date range change
const onDateRangeChange = (newDateRange: any) => {
  emit('filtersChanged', { ...filters.value, dateRange: newDateRange })
}

// Format helpers
const formatObjective = (objective: string) => {
  return objective.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}

const formatIndustry = (industry: string) => {
  return industry.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
}
</script>