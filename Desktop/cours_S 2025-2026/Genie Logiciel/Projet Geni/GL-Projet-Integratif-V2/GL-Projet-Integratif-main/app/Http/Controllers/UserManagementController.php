<?php

namespace App\Http\Controllers;

use App\Interfaces\UtilisateurFactoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;


class UserManagementController extends Controller
{
    
    private UtilisateurFactoryInterface $factory;

    public function __construct(UtilisateurFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Crée un fournisseur (role_id = 3).
     * Le controller ne sait pas comment User est construit → Low Coupling.
     */
    public function createFournisseur(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        // Seul l'admin (role_id = 1) peut créer des fournisseurs
        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'avez pas l'autorisation",
                'data'    => [],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8',
            'idFiscale' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data'    => [],
            ], 422);
        }

        // Le controller délègue TOUT à la factory → couplage faible
        $newUser = $this->factory->creer([
            'name'      => $request->input('name'),
            'email'     => $request->input('email'),
            'password'  => $request->input('password'),
            'role_id'   => 3, // Fournisseur
            'idFiscale' => $request->input('idFiscale'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Fournisseur créé avec succès',
            'data'    => ['user' => $newUser],
        ]);
    }

    /**
     * Crée un agent (role_id = 2, 4, 5 ou 6 selon le type).
     * Même logique — le controller ne connaît pas les détails de User.
     */
    public function createAgent(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => "Vous n'avez pas l'autorisation",
                'data'    => [],
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_id'  => 'required|integer|in:2,4,5,6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data'    => [],
            ], 422);
        }

        // Toujours la même ligne — peu importe le type d'agent
        $newUser = $this->factory->creer($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Agent créé avec succès',
            'data'    => ['user' => $newUser],
        ]);
    }
}