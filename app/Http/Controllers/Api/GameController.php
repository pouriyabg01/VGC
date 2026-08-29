<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\GameRequest;
use App\Http\Resources\GameResource;
use App\Models\Game;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

/**
 * @group Game Catalogue
 *
 * The games the site puts on. Reading is public; only an admin can change the
 * catalogue.
 */
class GameController extends BaseController
{
    use AuthorizesRequests;

    /**
     * List all games
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "all games",
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Tekken 8",
     *       "image_url": "http://localhost/storage/games/tekken.jpg"
     *     }
     *   ]
     * }
     */
    public function index()
    {
        return $this->sendResponse(
            GameResource::collection(Game::latest()->get()),
            'all games',
            200
        );
    }

    /**
     * Show a game
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "game",
     *   "data": { "id": 1, "title": "Tekken 8", "image_url": null }
     * }
     */
    public function show(Game $game)
    {
        return $this->sendResponse(new GameResource($game), 'game', 200);
    }

    /**
     * Add a game
     *
     * @authenticated
     *
     * @bodyParam title string required The game's name. Example: Tekken 8
     * @bodyParam image file The cover. JPG, PNG or WEBP, up to 5 MB.
     *
     * @response 201 scenario="Created" {
     *   "success": true,
     *   "message": "game created",
     *   "data": { "id": 1, "title": "Tekken 8", "image_url": null }
     * }
     */
    public function store(GameRequest $request)
    {
        $this->authorize('create', Game::class);

        $game = Game::create([
            'title' => $request->validated('title'),
            'image' => $this->storeCover($request),
        ]);

        return $this->sendResponse(new GameResource($game), 'game created', 201);
    }

    /**
     * Update a game
     *
     * A new cover replaces the old one, and the file it replaces is deleted so
     * the disk does not fill with covers nothing points at.
     *
     * @authenticated
     *
     * @bodyParam title string The game's name. Example: Tekken 8
     * @bodyParam image file A replacement cover. JPG, PNG or WEBP, up to 5 MB.
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "game updated",
     *   "data": { "id": 1, "title": "Tekken 8", "image_url": null }
     * }
     */
    public function update(GameRequest $request, Game $game)
    {
        $this->authorize('update', $game);

        $data = array_filter(
            ['title' => $request->validated('title')],
            fn ($value): bool => $value !== null
        );

        if ($cover = $this->storeCover($request)) {
            $this->deleteCover($game);
            $data['image'] = $cover;
        }

        $game->update($data);

        return $this->sendResponse(new GameResource($game->fresh()), 'game updated', 200);
    }

    /**
     * Delete a game
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "game deleted",
     *   "data": []
     * }
     */
    public function destroy(Game $game)
    {
        $this->authorize('delete', $game);

        $this->deleteCover($game);
        $game->delete();

        return $this->sendResponse([], 'game deleted', 200);
    }

    /** Puts an uploaded cover on the public disk, or null when none was sent. */
    private function storeCover(GameRequest $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('games', 'public');

        // The public disk is configured with 'throw' => false, so a failed
        // write comes back as false rather than as an exception.
        return is_string($path) && $path !== '' ? $path : null;
    }

    private function deleteCover(Game $game): void
    {
        if ($game->image) {
            Storage::disk('public')->delete($game->image);
        }
    }
}
