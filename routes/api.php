<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\Admin\JobController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\AiPromptController;
use App\Http\Controllers\Admin\AiProviderController;
use App\Http\Controllers\Admin\ArticleSourceAiPromptController;
use App\Http\Controllers\Admin\ArticleSourceController;
use App\Http\Controllers\Admin\ScrapedArticleController;
use App\Http\Controllers\Admin\ScrapeHttpProfileController;
use App\Http\Controllers\Admin\SessionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Blog\PostCategoryController;
use App\Http\Controllers\Blog\PostController;
use App\Http\Controllers\Blog\PostImageController;
use App\Http\Controllers\Blog\PostTagController;
use App\Http\Controllers\Blog\PostViewController;
use App\Http\Controllers\SpreadsheetFormatterController;
use App\Http\Controllers\PostalCodeAddressController;
use App\Http\Controllers\Sgc\BrandController as SgcBrandController;
use App\Http\Controllers\Sgc\CompanyController as SgcCompanyController;
use App\Http\Controllers\Sgc\CompanyProductController as SgcCompanyProductController;
use App\Http\Controllers\Sgc\CustomerAddressController as SgcCustomerAddressController;
use App\Http\Controllers\Sgc\CustomerAttachmentController as SgcCustomerAttachmentController;
use App\Http\Controllers\Sgc\CustomerController as SgcCustomerController;
use App\Http\Controllers\Sgc\ProductCategoryController as SgcProductCategoryController;
use App\Http\Controllers\Sgc\ProductController as SgcProductController;
use App\Http\Controllers\Sgc\UnitOfMeasureController as SgcUnitOfMeasureController;
use App\Http\Controllers\Sgc\EmployeeController as SgcEmployeeController;
use App\Http\Controllers\Sgc\OrderController as SgcOrderController;
use App\Http\Controllers\Sgc\PaymentMethodController as SgcPaymentMethodController;
use App\Models\Role;

Route::prefix("auth")->group(function() {
    Route::post("/login", [
        AuthController::class, "login"
    ])->name("api.auth.login");

    Route::post("/register", [
        AuthController::class, "register"
    ])->name("api.auth.register");

    Route::post("/logout", [
        AuthController::class, "logout"
    ])->name("api.auth.logout");
});

Route::prefix("account")->group(function() {
    Route::post("/forgot-password", [
        AccountController::class, "forgotPassword",
    ])
        ->name("api.account.forgot-password")
        ->middleware("throttle:5,1");

    Route::post("/reset-password", [
        AccountController::class, "resetPassword",
    ])
        ->name("api.account.reset-password")
        ->middleware("throttle:5,1");

    Route::middleware("auth")->group(function() {
        Route::post("/email/verification-notification", [
            AccountController::class, "sendEmailVerificationNotification",
        ])
            ->name("api.account.email.verification-notification")
            ->middleware("throttle:6,1");

        Route::put("/personal-data", [
            AccountController::class, "updatePersonalData",
        ])->name("api.account.personal-data.update");

        Route::put("/password", [
            AccountController::class, "updatePassword",
        ])->name("api.account.password.update");

        Route::get("/sessions", [
            AccountController::class, "sessions",
        ])->name("api.account.sessions.index");

        Route::delete("/sessions/{session}", [
            AccountController::class, "revokeSession",
        ])->name("api.account.sessions.destroy");

        Route::post("/destroy", [
            AccountController::class, "destroy",
        ])->name("api.account.destroy");
    });
});

Route::middleware(["auth", "role:" . Role::SYSTEM_ADMIN])
    ->group(function () {
        Route::get("/sessions", [
            SessionController::class, "index",
        ])->name("api.sessions.index");

        Route::get("/users", [
            UserController::class, "index",
        ])->name("api.users.index");

        Route::get("/jobs", [
            JobController::class, "index",
        ])->name("api.jobs.index");

        Route::get("/jobs/spreadsheet-formatter", [
            JobController::class, "spreadsheetFormatter",
        ])->name("api.jobs.spreadsheet-formatter");

        Route::get("/logs/files", [
            LogController::class, "indexFiles",
        ])->name("api.logs.files.index");

        Route::get("/logs/files/daily/{date}", [
            LogController::class, "showDaily",
        ])->name("api.logs.files.daily.show");

        Route::delete("/logs/files/daily/{date}", [
            LogController::class, "clearDaily",
        ])->name("api.logs.files.daily.clear");

        Route::get("/logs/files/{filename}", [
            LogController::class, "showFile",
        ])->name("api.logs.files.show");

        Route::delete("/logs/files/{filename}", [
            LogController::class, "clearFile",
        ])->name("api.logs.files.clear");

        Route::get("/article-sources", [
            ArticleSourceController::class, "index",
        ])->name("api.article-sources.index");

        Route::post("/article-sources", [
            ArticleSourceController::class, "store",
        ])->name("api.article-sources.store");

        Route::post("/article-sources/test", [
            ArticleSourceController::class, "test",
        ])->name("api.article-sources.test");

        Route::put("/article-sources/{article_source}", [
            ArticleSourceController::class, "update",
        ])->name("api.article-sources.update");

        Route::patch("/article-sources/{article_source}/disable", [
            ArticleSourceController::class, "disable",
        ])->name("api.article-sources.disable");

        Route::get("/article-sources/{article_source}/ai-prompts", [
            ArticleSourceAiPromptController::class, "index",
        ])->name("api.article-sources.ai-prompts.index");

        Route::put("/article-sources/{article_source}/ai-prompts", [
            ArticleSourceAiPromptController::class, "sync",
        ])->name("api.article-sources.ai-prompts.sync");

        Route::get("/ai-providers", [
            AiProviderController::class, "index",
        ])->name("api.ai-providers.index");

        Route::post("/ai-providers", [
            AiProviderController::class, "store",
        ])->name("api.ai-providers.store");

        Route::put("/ai-providers/{ai_provider}", [
            AiProviderController::class, "update",
        ])->name("api.ai-providers.update");

        Route::get("/ai-prompt-templates/{name}", [
            AiPromptController::class, "template",
        ])->name("api.ai-prompt-templates.show");

        Route::get("/ai-prompts", [
            AiPromptController::class, "index",
        ])->name("api.ai-prompts.index");

        Route::get("/ai-providers/{ai_provider}/prompts", [
            AiPromptController::class, "forProvider",
        ])->name("api.ai-providers.prompts.index");

        Route::post("/ai-providers/{ai_provider}/prompts", [
            AiPromptController::class, "store",
        ])->name("api.ai-providers.prompts.store");

        Route::put("/ai-prompts/{ai_prompt}", [
            AiPromptController::class, "update",
        ])->name("api.ai-prompts.update");

        Route::delete("/ai-prompts/{ai_prompt}", [
            AiPromptController::class, "destroy",
        ])->name("api.ai-prompts.destroy");

        Route::get("/scraped-articles", [
            ScrapedArticleController::class, "index",
        ])->name("api.scraped-articles.index");

        Route::post("/scraped-articles/scrape", [
            ScrapedArticleController::class, "scrape",
        ])->name("api.scraped-articles.scrape");

        Route::get("/scraped-articles/{scraped_article}", [
            ScrapedArticleController::class, "show",
        ])->name("api.scraped-articles.show");

        Route::patch("/scraped-articles/{scraped_article}/disable", [
            ScrapedArticleController::class, "disable",
        ])->name("api.scraped-articles.disable");

        Route::patch("/scraped-articles/{scraped_article}/reject-translation", [
            ScrapedArticleController::class, "rejectTranslation",
        ])->name("api.scraped-articles.reject-translation");

        Route::patch("/scraped-articles/{scraped_article}/reset-translation-attempts", [
            ScrapedArticleController::class, "resetTranslationAttempts",
        ])->name("api.scraped-articles.reset-translation-attempts");

        Route::get("/scrape-http-profiles", [
            ScrapeHttpProfileController::class, "index",
        ])->name("api.scrape-http-profiles.index");

        Route::post("/scrape-http-profiles", [
            ScrapeHttpProfileController::class, "store",
        ])->name("api.scrape-http-profiles.store");

        Route::put("/scrape-http-profiles/{scrape_http_profile}", [
            ScrapeHttpProfileController::class, "update",
        ])->name("api.scrape-http-profiles.update");

        Route::delete("/scrape-http-profiles/{scrape_http_profile}", [
            ScrapeHttpProfileController::class, "destroy",
        ])->name("api.scrape-http-profiles.destroy");
    });

Route::get("/postal-code/{postal_code}", [
    PostalCodeAddressController::class, "show",
])->name("api.postal-code.show");

Route::prefix("tools")->group(function() {
    Route::post("/spreadsheet-formatter/upload", [
        SpreadsheetFormatterController::class, "upload"
    ])
        ->name("api.tools.spreadsheet-formatter.upload")
        ->middleware("throttle:5,1");

    Route::get("/spreadsheet-formatter/files/status", [
        SpreadsheetFormatterController::class, "status"
    ])->name("api.tools.spreadsheet-formatter.files.status");

    Route::delete("/spreadsheet-formatter/files/{uuid}", [
        SpreadsheetFormatterController::class, "destroy"
    ])->name("api.tools.spreadsheet-formatter.files.destroy");

    Route::prefix("sgc")->middleware("auth")->group(function () {
        Route::get("/companies", [
            SgcCompanyController::class, "index",
        ])->name("api.tools.sgc.companies.index");

        Route::post("/companies", [
            SgcCompanyController::class, "store",
        ])->name("api.tools.sgc.companies.store");

        Route::get("/companies/{sgc_company}", [
            SgcCompanyController::class, "show",
        ])->name("api.tools.sgc.companies.show");

        Route::put("/companies/{sgc_company}", [
            SgcCompanyController::class, "update",
        ])->name("api.tools.sgc.companies.update");

        Route::delete("/companies/{sgc_company}", [
            SgcCompanyController::class, "destroy",
        ])->name("api.tools.sgc.companies.destroy");

        Route::get("/customers", [
            SgcCustomerController::class, "index",
        ])->name("api.tools.sgc.customers.index");

        Route::get("/customers/lookup", [
            SgcCustomerController::class, "lookup",
        ])->name("api.tools.sgc.customers.lookup");

        Route::post("/customers", [
            SgcCustomerController::class, "store",
        ])->name("api.tools.sgc.customers.store");

        Route::get("/customers/{sgc_customer}", [
            SgcCustomerController::class, "show",
        ])->name("api.tools.sgc.customers.show");

        Route::put("/customers/{sgc_customer}", [
            SgcCustomerController::class, "update",
        ])->name("api.tools.sgc.customers.update");

        Route::delete("/customers/{sgc_customer}", [
            SgcCustomerController::class, "destroy",
        ])->name("api.tools.sgc.customers.destroy");

        Route::get("/customers/{sgc_customer}/addresses", [
            SgcCustomerAddressController::class, "index",
        ])->name("api.tools.sgc.customers.addresses.index");

        Route::post("/customers/{sgc_customer}/addresses", [
            SgcCustomerAddressController::class, "store",
        ])->name("api.tools.sgc.customers.addresses.store");

        Route::put("/customers/{sgc_customer}/addresses/{sgc_customer_address}", [
            SgcCustomerAddressController::class, "update",
        ])->name("api.tools.sgc.customers.addresses.update");

        Route::delete("/customers/{sgc_customer}/addresses/{sgc_customer_address}", [
            SgcCustomerAddressController::class, "destroy",
        ])->name("api.tools.sgc.customers.addresses.destroy");

        Route::get("/customers/{sgc_customer}/attachments", [
            SgcCustomerAttachmentController::class, "index",
        ])->name("api.tools.sgc.customers.attachments.index");

        Route::post("/customers/{sgc_customer}/attachments", [
            SgcCustomerAttachmentController::class, "store",
        ])->name("api.tools.sgc.customers.attachments.store");

        Route::get("/customers/{sgc_customer}/attachments/{sgc_customer_attachment}", [
            SgcCustomerAttachmentController::class, "show",
        ])->name("api.tools.sgc.customers.attachments.show");

        Route::delete("/customers/{sgc_customer}/attachments/{sgc_customer_attachment}", [
            SgcCustomerAttachmentController::class, "destroy",
        ])->name("api.tools.sgc.customers.attachments.destroy");

        Route::get("/brands", [
            SgcBrandController::class, "index",
        ])->name("api.tools.sgc.brands.index");

        Route::post("/brands", [
            SgcBrandController::class, "store",
        ])->name("api.tools.sgc.brands.store");

        Route::put("/brands/{sgc_brand}", [
            SgcBrandController::class, "update",
        ])->name("api.tools.sgc.brands.update");

        Route::delete("/brands/{sgc_brand}", [
            SgcBrandController::class, "destroy",
        ])->name("api.tools.sgc.brands.destroy");

        Route::get("/units-of-measure", [
            SgcUnitOfMeasureController::class, "index",
        ])->name("api.tools.sgc.units-of-measure.index");

        Route::post("/units-of-measure", [
            SgcUnitOfMeasureController::class, "store",
        ])->name("api.tools.sgc.units-of-measure.store");

        Route::put("/units-of-measure/{sgc_unit_of_measure}", [
            SgcUnitOfMeasureController::class, "update",
        ])->name("api.tools.sgc.units-of-measure.update");

        Route::delete("/units-of-measure/{sgc_unit_of_measure}", [
            SgcUnitOfMeasureController::class, "destroy",
        ])->name("api.tools.sgc.units-of-measure.destroy");

        Route::get("/product-categories", [
            SgcProductCategoryController::class, "index",
        ])->name("api.tools.sgc.product-categories.index");

        Route::post("/product-categories", [
            SgcProductCategoryController::class, "store",
        ])->name("api.tools.sgc.product-categories.store");

        Route::put("/product-categories/{sgc_product_category}", [
            SgcProductCategoryController::class, "update",
        ])->name("api.tools.sgc.product-categories.update");

        Route::delete("/product-categories/{sgc_product_category}", [
            SgcProductCategoryController::class, "destroy",
        ])->name("api.tools.sgc.product-categories.destroy");

        Route::get("/company-products", [
            SgcCompanyProductController::class, "index",
        ])->name("api.tools.sgc.company-products.index");

        Route::get("/products", [
            SgcProductController::class, "index",
        ])->name("api.tools.sgc.products.index");

        Route::post("/products", [
            SgcProductController::class, "store",
        ])->name("api.tools.sgc.products.store");

        Route::get("/products/{sgc_product}", [
            SgcProductController::class, "show",
        ])->name("api.tools.sgc.products.show");

        Route::put("/products/{sgc_product}", [
            SgcProductController::class, "update",
        ])->name("api.tools.sgc.products.update");

        Route::delete("/products/{sgc_product}", [
            SgcProductController::class, "destroy",
        ])->name("api.tools.sgc.products.destroy");

        Route::get("/products/{sgc_product}/stock-movements", [
            SgcProductController::class, "stockMovements",
        ])->name("api.tools.sgc.products.stock-movements.index");

        Route::get("/employees", [
            SgcEmployeeController::class, "index",
        ])->name("api.tools.sgc.employees.index");

        Route::post("/employees", [
            SgcEmployeeController::class, "store",
        ])->name("api.tools.sgc.employees.store");

        Route::get("/employees/{sgc_company_user}", [
            SgcEmployeeController::class, "show",
        ])->name("api.tools.sgc.employees.show");

        Route::put("/employees/{sgc_company_user}", [
            SgcEmployeeController::class, "update",
        ])->name("api.tools.sgc.employees.update");

        Route::delete("/employees/{sgc_company_user}", [
            SgcEmployeeController::class, "destroy",
        ])->name("api.tools.sgc.employees.destroy");

        Route::get("/payment-methods", [
            SgcPaymentMethodController::class, "index",
        ])->name("api.tools.sgc.payment-methods.index");

        Route::get("/orders", [
            SgcOrderController::class, "index",
        ])->name("api.tools.sgc.orders.index");

        Route::post("/orders", [
            SgcOrderController::class, "store",
        ])->name("api.tools.sgc.orders.store");

        Route::get("/orders/{sgc_order}", [
            SgcOrderController::class, "show",
        ])->name("api.tools.sgc.orders.show");

        Route::put("/orders/{sgc_order}", [
            SgcOrderController::class, "update",
        ])->name("api.tools.sgc.orders.update");

        Route::delete("/orders/{sgc_order}", [
            SgcOrderController::class, "destroy",
        ])->name("api.tools.sgc.orders.destroy");

        Route::post("/orders/{sgc_order}/cancel", [
            SgcOrderController::class, "cancel",
        ])->name("api.tools.sgc.orders.cancel");

        Route::post("/orders/{sgc_order}/installments/{sgc_order_installment}/pay", [
            SgcOrderController::class, "payInstallment",
        ])->name("api.tools.sgc.orders.installments.pay");
    });
});

Route::prefix("blog/posts")->group(function () {
    Route::get("/", [
        PostController::class, "list",
    ])->name("api.blog.posts.index");

    Route::get("/categories", [
        PostCategoryController::class, "index",
    ])->name("api.blog.posts.categories.index");

    Route::get("/tags", [
        PostTagController::class, "index",
    ])->name("api.blog.posts.tags.index");

    Route::post("/{post}/views", [
        PostViewController::class, "store",
    ])
        ->middleware("throttle:10,1")
        ->name("api.blog.posts.views.store");

    Route::middleware(["auth", "role:" . Role::SYSTEM_ADMIN])->group(function () {
        Route::get("/images", [
            PostImageController::class, "index",
        ])->name("api.blog.posts.images.index");

        Route::post("/images", [
            PostImageController::class, "store",
        ])->name("api.blog.posts.images.store");

        Route::post("/images/associate", [
            PostImageController::class, "associate",
        ])->name("api.blog.posts.images.associate");

        Route::delete("/images/{post_image}", [
            PostImageController::class, "destroy",
        ])->name("api.blog.posts.images.destroy");

        Route::get("/{post}/views", [
            PostViewController::class, "index",
        ])->name("api.blog.posts.views.index");

        Route::get("/{post}", [
            PostController::class, "getById",
        ])->name("api.blog.posts.get-by-id");

        Route::post("/", [
            PostController::class, "store",
        ])->name("api.blog.posts.store");

        Route::put("/{post}", [
            PostController::class, "update",
        ])->name("api.blog.posts.update");

        Route::delete("/{post}", [
            PostController::class, "destroy",
        ])->name("api.blog.posts.destroy");

        Route::post("/categories", [
            PostCategoryController::class, "store",
        ])->name("api.blog.posts.categories.store");

        Route::put("/categories/{post_category}", [
            PostCategoryController::class, "update",
        ])->name("api.blog.posts.categories.update");

        Route::delete("/categories/{post_category}", [
            PostCategoryController::class, "destroy",
        ])->name("api.blog.posts.categories.destroy");

        Route::post("/tags", [
            PostTagController::class, "store",
        ])->name("api.blog.posts.tags.store");

        Route::put("/tags/{post_tag}", [
            PostTagController::class, "update",
        ])->name("api.blog.posts.tags.update");

        Route::delete("/tags/{post_tag}", [
            PostTagController::class, "destroy",
        ])->name("api.blog.posts.tags.destroy");
    });
});
