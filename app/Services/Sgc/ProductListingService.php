<?php

namespace App\Services\Sgc;

use App\Helpers\SearchQuery;
use App\Models\SgcCompany;
use App\Models\SgcCompanyProduct;
use App\Models\SgcProduct;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductListingService
{
    public function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min($request->integer("per_page", $default), $max);
    }

    public function grouped(Request $request): LengthAwarePaginator
    {
        $pivots = DB::table("sgc_company_product")
            ->select([
                "product_id",
                "company_id",
                DB::raw("min(id) as id"),
                DB::raw("sum(stock_quantity) as stock_quantity"),
                DB::raw("count(*) as units_count"),
                DB::raw("min(serial_number) as serial_number"),
                DB::raw("min(sale_price) as sale_price"),
                DB::raw("max(sale_price) as max_sale_price"),
                DB::raw("min(cost_price) as cost_price"),
                DB::raw("max(cost_price) as max_cost_price"),
                DB::raw("min(unit_of_measure_id) as unit_of_measure_id"),
            ])
            ->groupBy("product_id", "company_id");

        $query = SgcProduct::query()
            ->leftJoinSub($pivots, "sgc_company_product", function ($join) use ($request) {
                $join->on("sgc_products.id", "=", "sgc_company_product.product_id");

                if( $request->filled("company_id") ) {
                    $join->where(
                        "sgc_company_product.company_id",
                        $request->integer("company_id")
                    );

                    return;
                }

                $join->whereIn(
                    "sgc_company_product.company_id",
                    SgcCompany::query()
                        ->accessibleBy($request->user())
                        ->select("sgc_companies.id")
                );
            });

        $this->joinCatalog($query, "sgc_products");
        $this->joinUnitOfMeasure($query, "sgc_company_product");

        $query
            ->select([
                "sgc_products.*",
                "sgc_brands.name as brand_name",
                "sgc_product_categories.name as category_name",
                "sgc_company_product.id as company_product_id",
                "sgc_company_product.company_id",
                "sgc_company_product.sale_price",
                "sgc_company_product.max_sale_price",
                "sgc_company_product.cost_price",
                "sgc_company_product.max_cost_price",
                "sgc_company_product.stock_quantity",
                "sgc_company_product.serial_number",
                "sgc_company_product.units_count",
                "sgc_company_product.unit_of_measure_id",
                "sgc_units_of_measure.abbreviation as unit_of_measure",
            ])
            ->when($request->filled("company_id"), function ($query) use ($request) {
                $query->where(
                    "sgc_company_product.company_id",
                    $request->integer("company_id")
                );
            })
            ->whereHas("companies", function ($query) use ($request) {
                $query->accessibleBy($request->user());

                if( $request->filled("company_id") ) {
                    $query->where("sgc_companies.id", $request->integer("company_id"));
                }

                if( $request->filled("company") ) {
                    $company = $request->input("company");

                    $query->where(function ($query) use ($company) {
                        SearchQuery::applySubstringSearch($query, "sgc_companies.legal_name", $company);

                        $query->orWhere(function ($query) use ($company) {
                            SearchQuery::applySubstringSearch($query, "sgc_companies.trade_name", $company);
                        });
                    });
                }
            });

        $this->applyProductFilters(
            $query,
            $request,
            "sgc_products.name",
            "concat_ws(' ', sgc_products.name, sgc_brands.name, sgc_product_categories.name)",
        );

        return $query
            ->with([
                "companies" => function ($query) use ($request) {
                    $query->accessibleBy($request->user());

                    if( $request->filled("company_id") ) {
                        $query->where("sgc_companies.id", $request->integer("company_id"));
                    }
                },
            ])
            ->orderByDesc("sgc_products.id")
            ->paginate($this->perPage($request));
    }

    public function companyProducts(Request $request, SgcCompany $company): LengthAwarePaginator
    {
        $query = SgcCompanyProduct::query()
            ->join(
                "sgc_products",
                "sgc_company_product.product_id",
                "=",
                "sgc_products.id"
            )
            ->where("sgc_company_product.company_id", $company->id);

        $this->joinCatalog($query, "sgc_products");
        $this->joinUnitOfMeasure($query, "sgc_company_product");

        $query->select([
            "sgc_products.id",
            "sgc_products.name",
            "sgc_products.brand_id",
            "sgc_products.product_category_id",
            "sgc_products.created_at",
            "sgc_products.updated_at",
            "sgc_brands.name as brand_name",
            "sgc_product_categories.name as category_name",
            "sgc_company_product.id as company_product_id",
            "sgc_company_product.company_id",
            "sgc_company_product.sale_price",
            "sgc_company_product.cost_price",
            "sgc_company_product.stock_quantity",
            "sgc_company_product.serial_number",
            "sgc_company_product.unit_of_measure_id",
            "sgc_units_of_measure.abbreviation as unit_of_measure",
        ]);

        if( $request->filled("product_id") ) {
            $query->where("sgc_products.id", $request->integer("product_id"));
        }

        $this->applyProductFilters(
            $query,
            $request,
            "sgc_products.name",
            "concat_ws(' ', sgc_products.name, sgc_brands.name, sgc_product_categories.name, sgc_company_product.serial_number)",
        );

        $this->applyStockFilter($query, $request);

        return $query
            ->orderByDesc("sgc_company_product.id")
            ->paginate($this->perPage($request));
    }

    private function joinCatalog(Builder $query, string $products_table): void
    {
        $query
            ->leftJoin(
                "sgc_brands",
                "{$products_table}.brand_id",
                "=",
                "sgc_brands.id"
            )
            ->leftJoin(
                "sgc_product_categories",
                "{$products_table}.product_category_id",
                "=",
                "sgc_product_categories.id"
            );
    }

    private function joinUnitOfMeasure(Builder $query, string $pivot_table): void
    {
        $query->leftJoin(
            "sgc_units_of_measure",
            "{$pivot_table}.unit_of_measure_id",
            "=",
            "sgc_units_of_measure.id"
        );
    }

    private function applyProductFilters(
        Builder $query,
        Request $request,
        string $name_column,
        string $search_expression,
    ): void {
        $query
            ->when($request->filled("id"), function ($query) use ($request) {
                $query->where("sgc_products.id", $request->integer("id"));
            })
            ->when($request->filled("name"), function ($query) use ($request, $name_column) {
                SearchQuery::applySubstringSearch(
                    $query,
                    $name_column,
                    $request->input("name")
                );
            })
            ->when($request->filled("search"), function ($query) use ($request, $search_expression) {
                SearchQuery::applyWordSearch(
                    $query,
                    $search_expression,
                    $request->input("search")
                );
            });
    }

    private function applyStockFilter(Builder $query, Request $request): void
    {
        $stock = $request->input("stock", "all");

        if( $stock === "in_stock" ) {
            $query->where("sgc_company_product.stock_quantity", ">", 0);
        }

        if( $stock === "out_of_stock" ) {
            $query->where("sgc_company_product.stock_quantity", "<=", 0);
        }
    }
}
