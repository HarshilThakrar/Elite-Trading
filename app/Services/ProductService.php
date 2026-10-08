<?php

namespace App\Services;

use App\Repositories\ProductRepositoryInterface;

class ProductService extends BaseService
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getAllProducts()
    {
        return $this->productRepository->all();
    }

    public function createProduct(array $data)
    {
        return $this->productRepository->create($data);
    }

    public function getProductById($id)
    {
        return $this->productRepository->find($id);
    }

    public function updateProduct($id, array $data)
    {
        return $this->productRepository->update($id, $data);
    }

    public function deleteProduct($id)
    {
        return $this->productRepository->delete($id);
    }
}
