<?php

namespace App\Http\Controllers;

use App\Type;
use App\Resource;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;


class ResourcesController extends Controller
{
    /**
     * @param $type
     * @param $identity
     * @return \Illuminate\Http\Response
     */
    public function get($type, $identity)
    {
        /** @var \App\Resource $resource */
        $resource = Resource::query()
            ->where('type_id', Type::getByChar($type))
            ->where('identity', $identity)
            ->first();

        /** Custom 404 message */
        if ($resource == null) {
            abort(404, 'Resource not found or was trashed');
        }

        /** Update downloads count */
        $resource->update(['reviews_count' => $resource->reviews_count + 1]);

        switch (optional($resource)->type_id) {
            /** Return a file */
            case Type::$FILE:
                return response()->download(storage_path('files/' . $resource->internal_identity), $resource->name);
            /** Redirect to link */
            case Type::$LINK:
                return redirect()->to($resource->internal_identity);
            /** Not implemented */
            default:
                return abort(400, 'Invalid request');
        }
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function files(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|file',
            'is_public' => 'boolean',
            'is_private' => 'boolean',
        ]);

        $file = $request->file('file');

        /** @var \App\Resource $resource */
        $resource = Resource::create([
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'user_id' => optional($request->user())->id,

            'type_id' => Type::$FILE,
            'identity' => str_random(env('IDENTITY_LEN', 10)),
            'internal_identity' => str_random() . '.' . $file->getClientOriginalExtension(),

            'is_public' => $request->get('is_public', 1),
            'is_private' => $request->get('is_private', 0),
        ]);

        $file->move(storage_path('files'), $resource->internal_identity);
        $resource->append(['status_string', 'is_deleted']);
        return response()->json([
            'status' => 'success',
            'data' => $resource->toArray()
        ], 201);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Validation\ValidationException
     */
    public function links(Request $request)
    {
        $this->validate($request, [
            'link' => 'required|url',
            'is_public' => 'boolean',
            'is_private' => 'boolean',
        ]);

        /** @var \App\Resource $resource */
        $resource = Resource::create([
            'name' => $request->get('link'),
            'size' => null,

            'type_id' => Type::$LINK,
            'internal_identity' => $request->get('link'),
            'identity' => str_random(env('IDENTITY_LEN', 10)),

            'is_public' => $request->get('is_public', 1),
            'is_private' => $request->get('is_private', 0),

            'user_id' => optional($request->user())->id,
        ]);

        $resource->append(['status_string', 'is_deleted']);
        return response()->json([
            'status' => 'success',
            'data' => $resource->toArray()
        ], 201);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $resources = Resource::query()
            ->with('user')
            ->where('is_public', 1)
            // TODO: This check is invalid!
            ->where('is_private', 0)
            ->orWhere('user_id', optional($request->user())->id);

        if (optional($request->user())->id == 1) {
            $resources = Resource::query();
        }
        /** @var Collection|Resource|\Illuminate\Database\Eloquent\Builder $resources */
        $resources = $resources
            ->orderBy('id')
            ->get(['id', 'name', 'identity', 'type_id', 'size', 'user_id', 'created_at']);
        return response()->json([
            'status' => 'success',
            'data' => $resources->toArray()
        ]);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var \App\Resource $resource */
        $resource = Resource::withTrashed()->findOrFail($id);
        $resource->append(['status_string', 'user_string', 'is_deleted']);
        return response()->json([
            'status' => 'success',
            'data' => $resource->attributesToArray()
        ]);
    }

    /**
     * @param $id
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update($id, Request $request)
    {
        /** @var \App\Resource $resource */
        $resource = Resource::withTrashed()->findOrFail($id);

        $this->authorize('manage.resource', $resource);
        $this->validate($request, [
            'is_public' => 'boolean',
            'is_private' => 'boolean',
        ]);

        $resource->fill($request->only(['is_public', 'is_private']));
        $resource->save();
        $resource->append(['status_string', 'user_string', 'is_deleted']);

        return response()->json([
            'status' => 'success',
            'data' => $resource->attributesToArray()
        ], 200);
    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function delete($id)
    {
        /** @var \App\Resource $resource */
        $resource = Resource::findOrFail($id);
        $this->authorize('manage.resource', $resource);
        if ($resource->type_string == 'f')
            unlink(storage_path('files/' . $resource->internal_identity));
        $resource->delete();
        return response()->json([
            'status' => 'success',
            'data' => $resource->attributesToArray()
        ], 204);
    }
}
