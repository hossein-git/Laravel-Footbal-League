<?php

namespace Modules\Base\Repositories;

use Exception;
use Illuminate\Container\Container as Application;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;


/**
 * Class BaseRepository
 * @package Modules\Base\Repositories;
 * @author  Hossein Haghparast
 */
abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Application
     */
    protected $app;


    public function __construct(Application $app,$model = null)
    {
        $this->app = $app;
        $this->model = $model ?? $this->makeModel();
    }

    /**
     * Make Model instance
     *
     * @return Model
     * @throws Exception
     *
     */
    public function makeModel()
    {
        $model = $this->app->make($this->model());

        if (!$model instanceof Model) {
            throw new Exception("Class {$this->model()} must be an instance of Illuminate\\Database\\Eloquent\\Model");
        }

        return $this->model = $model;
    }

    /**
     * Configure the Model
     *
     */
    abstract public function model();

    /**
     * Configure the Cache Key
     *
     * @return string
     */
    abstract public function cacheKey(): string;

    /**
     * Paginate records for scaffold.
     * @param string   $search
     * @param          $perPage
     * @param string[] $columns
     * @param array    $relations
     * @return LengthAwarePaginator
     */
    public function paginate($search = '', $perPage=10, array $relations = [], $columns = ['*'])
    {
        $query = $this->allQuery($search, $relations);

        return $query->paginate($perPage, $columns);
    }

    /**
     * Build a query for retrieving all records.
     *
     * @param string   $search
     * @param int|null $skip
     * @param int|null $limit
     * @param array    $relations
     * @return Builder
     */
    public function allQuery($search = '', array $relations = [], $skip = null, $limit = null)
    {
        $query = $this->model->newQuery()->with($relations);

        if ($search) {
            $searchable = $this->getFieldsSearchable();
            $query->where(
                function ($q) use ($searchable, $search) {
                    foreach ($searchable as $field) {
                        $q->orWhere($field, 'like', "%{$search}%");
                    }
                }
            );
        }

        if (!is_null($skip)) {
            $query->skip($skip);
        }

        if (!is_null($limit)) {
            $query->limit($limit);
        }

        return $query;
    }

    /**
     * Get searchable fields array
     *
     * @return array
     */
    abstract public function getFieldsSearchable(): array;

    /**
     * Retrieve all records with given filter criteria
     *
     * @param array    $search
     * @param array    $relations
     * @param int|null $skip
     * @param int|null $limit
     * @param array    $columns
     *
     * @return LengthAwarePaginator|Builder[]|Collection
     */
    public function all($search = '', array $relations = [], $skip = null, $limit = null, $columns = ['*'])
    {
        $query = $this->allQuery($search, $relations, $skip, $limit);
        return $query->get($columns);
    }


    /**
     * Create model record
     *
     * @param array $input
     *
     * @return Model
     */
    public function create(array $input): Model
    {
        $this->clearCache();
        $model = $this->model->newInstance($input);

        $model->save();

        return $model;
    }

    /**
     *  if cache key exists this will delete it after CRUD
     */
    protected function clearCache()
    {
        if (isset($this->cacheKey) && Cache::has($this->cacheKey)) {
            Cache::forget($this->cacheKey);
        }
    }

    /**
     * Update model record for given id
     *
     * @param array $input
     * @param int   $id
     *
     * @return Builder|Builder[]|Collection|Model
     */
    public function update(array $input, int $id)
    {
        $this->clearCache();
        $this->checkId($id);

        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        $model->fill($input);

        $model->save();

        return $model;
    }

    /**
     * check input id
     * @param $id
     * @return JsonResponse/Void
     */
    public function checkId($id)
    {
        if (!is_int($id)) {
            return response()->json(['success' => 'false', 'message' => __('notValidId')], 404);
        }
    }

    /**
     * duplicate model
     * @param $id
     * @return Model
     */
    public function duplicate($id): Model
    {
        $model = $this->find($id);
        $this->clearCache();
        return $model->replicate();
    }

    /**
     * Find model record for given id
     *
     * @param int   $id
     * @param array $load : relations
     * @param array $columns
     * @return Builder|Builder[]|Collection|Model|null
     */
    public function find(int $id, $load = [], $columns = ['*'])
    {
        $this->checkId($id);

        $query = $this->model->newQuery();

        return $query->findOrFail($id, $columns)->load($load);
    }

    /**
     * Get model record on column
     * @param $column
     * @param $value
     * @param array $relations
     * @return Builder
     */
    public function where($column,$value,$relations = [])
    {
        $query = $this->model->newQuery();

        return $query->where($column, $value)->with($relations);
    }

    /**
     * @param int $id
     *
     * @return bool|mixed|null
     * @throws Exception
     *
     */
    public function delete($id): ?bool
    {
        $this->clearCache();
        $this->checkId($id);

        $query = $this->model->newQuery();

        $model = $query->findOrFail($id);

        return $model->delete();
    }

    /**
     * delete completely
     * @param $id
     * @return bool
     * @throws Exception
     */
    public function forceDelete($id)
    {
        $this->clearCache();
        $model = $this->findWithTrash($id);

        return $model->forceDelete();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function findWithTrash(int $id)
    {
        $this->checkId($id);

        $query = $this->model->newQuery();

        return $query->withTrashed()->findOrFail($id);
    }

    /**
     * restore deleted item
     * @param $id
     * @return bool
     */
    public function restore($id)
    {
        $this->checkId($id);

        return $this->model->newQuery()->withTrashed()->findOrFail($id)->restore();
    }

    /**
     * pass response after update model
     * case of use (in controller):
     * $this->xxxRepo->passViewAfterUpdated($model, 'langKey');
     * @param $result
     * @param $modelLangKey
     * @return JsonResponse
     */
    public function passViewAfterUpdated($result, string $modelLangKey): JsonResponse
    {
        if ($result) {
            return response()->json(
                [
                    'success' => true,
                    'message' => __("models/$modelLangKey.singular") . ' ' . __('messages.edited'),
                ],
                201
            );
        }

        return response()->json(
            [
                'success' => false,
                'message' => __("models/$modelLangKey.singular") . ' ' . __('messages.editedFailed'),
            ],
            501
        );
    }

    /**
     *
     * case of use (in controller):
     * $this->xxxRepo->passViewAfterCreated($model, 'langKey');
     * @param             $model
     * @param string      $modelLangKey
     * @return JsonResponse|RedirectResponse
     */
    public function passViewAfterCreated($model, string $modelLangKey)
    {
        return response()->json(
            [
                'success' => true,
                'message' => __("models/$modelLangKey.singular") . ' ' . __('messages.saved'),
            ],
            200
        );
    }

    /**
     * case of use (in controller):
     * $this->xxxRepo->passViewAfterDeleted($result, 'langKey');
     * @param bool   $result
     * @param string $modelLangKey
     * @return JsonResponse
     */
    public function passViewAfterDeleted(bool $result, string $modelLangKey)
    {
        return $result
            ? response()->json(
                [
                    'success' => true,
                    'message' => __("models/$modelLangKey.singular") . ' ' . __('messages.deleted'),
                ],
                202
            )
            : response()->json(
                [
                    'success' => false,
                    'message' => __("models/$modelLangKey.singular") . ' ' . __('messages.deletedFailed'),
                ],
                500
            );
    }

    /**
     * case of use (in controller):
     * $this->xxxRepo->passResponse(true, 'langKey','msgKey');
     * @param bool   $result
     * @param string $modelLangKey
     * @param string $messageKey
     * @return JsonResponse
     */
    public function passResponse(bool $result, string $modelLangKey, string $messageKey): JsonResponse
    {
        return $result
            ? response()->json(
                [
                    'success' => true,
                    'message' => __("models/$modelLangKey.singular") . ' ' . __("messages.$messageKey"),
                ],
                200
            )
            : response()->json(
                [
                    'success' => false,
                    'message' => __("models/$modelLangKey.singular") . ' ' . __("messages.$messageKey" . "Failed"),
                ],
                500
            );
    }


}
