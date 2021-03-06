<?php

namespace OFFLINE\Mall\Classes\Index;

use Illuminate\Support\Collection;
use OFFLINE\Mall\Models\Currency;
use OFFLINE\Mall\Models\CustomerGroup;
use OFFLINE\Mall\Models\Variant;

class VariantEntry implements Entry
{
    const INDEX = 'variants';

    protected $variant;
    protected $data;
    protected $defaultCurrency;

    public function __construct(Variant $variant)
    {
        $this->variant = $variant;

        // Make sure variants inherit variant data again.
        session()->forget('mall.variants.disable-inheritance');

        $variant->loadMissing([
            'prices.currency',
            'property_values.property',
            'product.brand',
            'product.categories',
            'customer_group_prices',
            'product.customer_group_prices',
        ]);

        $product = $variant->product;

        $this->defaultCurrency = Currency::defaultCurrency();

        $data              = $variant->attributesToArray();
        $data['published'] = $variant->published && $product->published;
        $data['on_sale']   = $variant->on_sale;

        $data['category_id'] = $product->categories->pluck('id');

        $data['index']                 = self::INDEX;
        $data['property_values']       = $this->mapProps($variant->all_property_values);
        $data['sort_orders']           = $product->getSortOrders();
        $data['prices']                = $this->mapPrices($variant);
        $data['parent_prices']         = $this->mapPrices($product);
        $data['customer_group_prices'] = $this->mapCustomerGroupPrices($variant);

        if ($product->brand) {
            $data['brand'] = ['id' => $product->brand->id, 'slug' => $product->brand->slug];
        }

        $this->data = $data;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function withData(array $data): Entry
    {
        $this->data = array_merge($this->data, $data);

        return $this;
    }

    protected function mapPrices($model): Collection
    {
        return $model->prices->mapWithKeys(function ($price) {
            return [$price->currency->code => $price->integer];
        });
    }

    protected function mapCustomerGroupPrices($model): Collection
    {
        return CustomerGroup::get()->mapWithKeys(function ($group) use ($model) {
            return [
                $group->id => Currency::getAll()->mapWithKeys(function ($currency) use ($model, $group) {
                    $price = $model->groupPrice($group, $currency);
                    if ($price) {
                        return [$price->currency->code => $price->integer];
                    }

                    return null;
                })->filter(),
            ];
        });
    }

    protected function mapProps(?Collection $input): Collection
    {
        if ($input === null) {
            return collect();
        }

        return $input->groupBy('property_id')->map(function ($value) {
            return $value->pluck('index_value')->unique()->filter()->values();
        })->filter();
    }
}
