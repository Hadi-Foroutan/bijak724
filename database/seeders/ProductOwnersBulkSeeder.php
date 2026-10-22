<?php

namespace Database\Seeders;

use App\Interfaces\Company\ProductOwnerRepositoryInterface;
use App\Models\Company;
use App\Services\Company\CompanyTableService;
use Illuminate\Database\Seeder;

/**
 * ۵۰ صاحب کالای نمونه با نام و شماره‌های متفاوت.
 *
 * php artisan db:seed --class=ProductOwnersBulkSeeder
 *
 * با کد حمل (transportation_code) بروزرسانی می‌شود، پس اجرای دوباره رکورد تکراری نمی‌سازد.
 */
class ProductOwnersBulkSeeder extends Seeder
{
    private const COMPANY_ID = 1000;

    private const COUNT = 50;

    /** @var list<string> */
    private const FIRST_NAMES = [
        'علی', 'محمد', 'حسین', 'رضا', 'مهدی', 'امیر', 'سعید', 'حسن', 'جواد', 'مجید',
        'احمد', 'ابراهیم', 'کریم', 'یونس', 'فرهاد', 'بهرام', 'داریوش', 'کامران', 'پویا', 'ناصر',
        'اکبر', 'اصغر', 'مصطفی', 'یاسر', 'وحید',
    ];

    /** @var list<string> */
    private const LAST_NAMES = [
        'احمدی', 'محمدی', 'رضایی', 'حسینی', 'کریمی', 'موسوی', 'جعفری', 'صادقی', 'نوری', 'قاسمی',
        'اکبری', 'رحیمی', 'فرجی', 'یوسفی', 'زارعی', 'کاظمی', 'عباسی', 'مرادی', 'طاهری', 'شریفی',
    ];

    /** @var list<string> */
    private const BUSINESS_TYPES = [
        'بازرگانی', 'صنعتی', 'تجارت', 'کشاورزی', 'پتروشیمی', 'فولاد', 'مصالح ساختمانی', 'غذایی',
        'نساجی', 'صادرات', 'واردات', 'معدنی',
    ];

    public function __construct(
        protected ProductOwnerRepositoryInterface $productOwnerRepository,
        protected CompanyTableService $companyTableService,
    ) {}

    public function run(): void
    {
        $company = Company::query()->findOrFail(self::COMPANY_ID);

        $this->companyTableService->sync($company->id);

        $query = $this->productOwnerRepository->query($company->id);

        for ($i = 1; $i <= self::COUNT; $i++) {
            $first = self::FIRST_NAMES[($i * 7) % count(self::FIRST_NAMES)];
            $last = self::LAST_NAMES[($i * 3) % count(self::LAST_NAMES)];

            // هر سومین مورد شرکت است و بقیه شخص حقیقی.
            $name = $i % 3 === 0
                ? 'شرکت '.self::BUSINESS_TYPES[$i % count(self::BUSINESS_TYPES)]." {$last}"
                : "{$first} {$last}";

            $query->updateOrCreate(
                [
                    'owner_company_id' => $company->id,
                    'transportation_code' => (string) (2000 + $i),
                ],
                [
                    'name' => $name,
                    'phone' => '0912'.str_pad((string) (1000000 + $i * 7919 % 9000000), 7, '0', STR_PAD_LEFT),
                ],
            );
        }
    }
}
