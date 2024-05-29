<?php

namespace App\Http\Controllers;

use App\Models\BonDeCommande;
use App\Models\Facture;
use App\Models\Fournisseur;
use App\Models\FournisseursSansCompte;
use App\Models\Role;
use App\Models\TypesFactures;
use App\Models\User;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Client\Response as ClientResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use PhpParser\Node\NullableType;

class AdministrateurController extends Controller
{
    public function storeDefaultProfilePicture(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403); // 403 accés refusé
        }
        if (!$request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune image téléchargée',
                'data' => []
            ], 400);
        }


        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
            ]);
        }

        $image = $request->file('image');
        $fileName = "default." . $image->getClientOriginalExtension();
        $image->storeAs('settings/users/default/profile_picture', $fileName, 'image');

        return response()->json([
            'success' => true,
            'message' => "L'image par defaut du nouvel utilisateur a été mise à jour avec succès",
        ]);
    }





    public function createAgent(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403);
        }

        $messages = [
            'email.required' => 'Le champ email est requis.',
            'email.email' => 'L\'adresse email doit être une adresse email valide.',
            'email.string' => 'Le champ email doit être une chaîne de caractères.',
            'email.max' => 'L\'adresse email ne peut pas dépasser 255 caractères.',
            'email.unique' => 'Cette adresse email est déjà utilisée.',
            'email.regex' => 'Le format de l\'adresse email est invalide.',
            'name.string' => 'Le nom doit être une chaîne de caractères.',
            'name.max' => 'Le nom ne peut pas dépasser 30 caractères.',
            'role_id.required' => 'Le champ rôle est requis.',
            'password.required' => 'Le champ mot de passe est requis.',
            'password.min' => 'Le mot de passe doit avoir au moins 8 caractères.',
            'role_id.in' => 'Le champ rôle doit être un Agent',
            'phone.required' => 'Le champ téléphone est requis.',
        ];

        $validator = Validator::make($request->all(), [
            'email' => [
                'required',
                'email',
                'string',
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'name' => [
                'string',
                'max:30',
            ],
            'phone' => 'required',
            'role_id' => 'required|in:2,4,5,6',
            'type_facture_ids' => 'nullable',
        ], $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }


        $password = Str::random(9);
        $hashedPassword = Hash::make($password);


        $userData = [
            'email' => $request->input('email'),
            'name' => $request->input('name'),
            'role_id' => $request->input('role_id'),
            'image' => 'test.jpg',
            'isActive' => 1,
            'phone' => $request->input('phone'),
            'isTwoFactorEnabled' => 0,
            'password' => $hashedPassword,
        ];

        $user = User::create($userData);
        if ($request->has('type_facture_ids') && ($request->input('type_facture_ids') !== null)) {
            $user->type_facture_ids = $request->input('type_facture_ids');
            $user->save();
        }
        $user->NotificationCredentialsAgent($request->input('email'), $password);

        return response()->json([
            'success' => true,
            'message' => "L'agent a été créé avec succès",
            'data' => [
                'userCredentials' => ['email' => $user->email, 'password' => $password]
            ]
        ]);
    }










    function ajoutFournisseurs(Request $request)
    {

        $fournisseurs_sans_compte = $request->input('fournisseurs');


        $rules = [
            '*.name' => 'required|string|max:255',
            '*.email' => [
                'nullable',
                'email',
                'string',
                'max:255',
                'unique:fournisseurs_sans_comptes,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            '*.phone' => 'required|string|max:20',
            '*.idErp' => 'nullable|integer',
            '*.idFiscale' => 'nullable|string|max:50',
            '*.adress' => 'nullable|string|max:255',
            '*.nationnalites' => 'nullable|string|max:255',
        ];

        $validator = Validator::make($fournisseurs_sans_compte, $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des fournisseurs.',
                'errors' => $validator->errors(),
            ]);
        }

        foreach ($fournisseurs_sans_compte as $fournisseurData) {
            FournisseursSansCompte::create([
                'name' => $fournisseurData['name'],
                'email' => $fournisseurData['email'],
                'phone' => $fournisseurData['phone'],
                'idErp' => $fournisseurData['idErp'],
                'idFiscale' => $fournisseurData['idFiscale'],
                'adress' => $fournisseurData['adress'],
                'nationnalites' => $fournisseurData['nationnalites'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Fournisseurs importés avec succès .'
        ]);
    }


    public function ajoutBonDeCommande(Request $request)
    {
        $bons_de_commande = $request->input('bons_de_commande');

        $rules = [
            '*.num_commande' => 'required|integer',
            '*.created_by' => 'nullable|string|max:255',
            '*.idErp' => 'nullable|integer',
            '*.delai_paiement' => 'nullable|string|max:255',
            '*.four_idFiscale' => 'nullable|string|max:50',
        ];

        $validator = Validator::make($bons_de_commande, $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation des bons de commande.',
                'errors' => $validator->errors(),
            ]);
        }

        foreach ($bons_de_commande as $bonDeCommandeData) {
            BonDeCommande::create([
                'num_commande' => $bonDeCommandeData['num_commande'],
                'created_by' => $bonDeCommandeData['created_by'],
                'idErp' => $bonDeCommandeData['idErp'],
                'delai_paiement' => $bonDeCommandeData['delai_paiement'],
                'hasInvoice' => false,
                'four_idFiscale' => $bonDeCommandeData['four_idFiscale'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Bons de commande importés avec succès.'
        ]);
    }


    function rolesUser(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403);
        }

        $role = Role::all('id', 'name');

        return response()->json([
            'success' => true,
            'message' => 'les roles du systéme',
            'data' => [
                'role' => $role,
            ]
        ]);
    }




    function afficheUsers(Request $request)
    {

        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403);
        }


        $page = $request->query('page', 1);
        $nb = $request->query('nb', 10);

        $users = User::when($request->input('role_id'), function ($query, $roleId) {
            return $query->where('role_id', $roleId);
        })
            ->when($request->input('email'), function ($query, $email) {
                return $query->where('email', 'like', "%{$email}%");
            })
            ->paginate($nb, ['*'], 'page', $page);;
        foreach ($users as $user) {
            $user->makeHidden([
                'password',
                'phone',
                'image',
                'notification_toggle',
                'code_2FA',
                'code_2fa_created_at',
                'refresh_token',
                'refreshToken_created_at',
                'isTwoFactorEnabled',
                'idErp',
                'idFiscale',
                'adress',
                'nationnalites',
                'direction',
                'type_facture_ids',
                'remember_token',
                'created_at',
                'updated_at'
            ]);
            $user->role = [$user->role()->first()];
        }

        return response()->json([
            'success' => true,
            'message' => 'les utilisateurs',
            'data' => [
                'totalPages' => $users->lastPage(),
                'users' => $users->items(),
            ],
        ]);
    }


    public function editUser(Request $request)
    {
        $user = User::find($request->input('agentId'));

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
                'data' => [],
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'email' => [
                'email',
                'string',
                'max:255',
                'unique:users,email,' . $user->id,
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            ],
            'type_facture_ids' => 'nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors(),
                'data' => [],
            ]);
        }
        if ($request->has('email')) {
            $user->email = $request->input('email');
        }


        if ($request->has('type_facture_ids')) {
            $user->type_facture_ids = $request->input('type_facture_ids');
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès',
            'data' => [],
        ]);
    }


    public function toggleUserStatus(Request $request)
    {
        $userId = $request->input('userId');
        $isActive = $request->input('isActive', 0);

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
                'data' => [],
            ], 404);
        }
        $user->isActive = $isActive;
        $user->save();

        $statusMessage = $isActive ? 'Compte activé avec succès' : 'Compte désactivé avec succès';

        return response()->json([
            'success' => true,
            'message' => $statusMessage,
            'data' => [],
        ]);
    }



    /*public function numberOfUsers(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403);
        }

        

        $total = User::count();
        $admins = User::where('role_id', 1)->count();
        $bofAgents = User::where('role_id', 2)->count();
        $apAgents = User::where('role_id', 4)->count();
        $fiscliteAgents = User::where('role_id', 5)->count();
        $tresoererieAgents = User::where('role_id', 6)->count();
        $fournisseurs = User::where('role_id', 3)->count();

        return response()->json([
            "success" => true,
            'message' => "voici le nombre de chaque type utilisateur",
            'data' => [
                "users" => [
                    "total" => $total,
                    "admins" => $admins,
                    "bofAgents" => $bofAgents,
                    "apAgents" => $apAgents,
                    "fiscliteAgents" => $fiscliteAgents,
                    "tresoererieAgents" => $tresoererieAgents,
                    "fournisseurs" => $fournisseurs,
                ]
            ],
            'system' => []
        ]);
    }*/

    public function dashboardAdmin(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403);
        }

        // Retrieve disk space information
        $diskSpaceInfo = $this->getDiskSpaceInfo();

        // Retrieve user counts
        $total = User::count();
        $admins = User::where('role_id', 1)->count();
        $bofAgents = User::where('role_id', 2)->count();
        $apAgents = User::where('role_id', 4)->count();
        $fiscliteAgents = User::where('role_id', 5)->count();
        $tresoererieAgents = User::where('role_id', 6)->count();
        $fournisseurs = User::where('role_id', 3)->count();

        $enAttente = Facture::where('etat_id', 1)->count();
        $enCours = Facture::where('etat_id', 2)->where('validePar', '!=', 'Agent Trésorerie')->count();
        $validee = Facture::where('etat_id', 2)->where('validePar', 'Agent Trésorerie')->count();
        $refusee = Facture::where('etat_id', 3)->count();
        $totale = Facture::count();
        $nbFourTotaleSansCompte = FournisseursSansCompte::count();

        return response()->json([
            "success" => true,
            'message' => "voici dashboard Admin",
            'data' => [
                "users" => [
                    "total" => $total,
                    "admins" => $admins,
                    "bofAgents" => $bofAgents,
                    "apAgents" => $apAgents,
                    "fiscliteAgents" => $fiscliteAgents,
                    "tresoererieAgents" => $tresoererieAgents,
                    "fournisseurs" => $fournisseurs,
                ],
                "system" => $diskSpaceInfo,
                "facture" => [
                    'totale' => $totale,
                    "enAttente" => $enAttente,
                    'enCours' => $enCours,
                    'validee' => $validee,
                    'refusee' => $refusee,
                ],
                'fournisseurEnAttente' => $nbFourTotaleSansCompte,
            ],
        ]);
    }

    private function getDiskSpaceInfo()
    {
        // Get total space, free space, and used space
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');
        $usedSpace = $totalSpace - $freeSpace;

        // Calculate the percentage of used and free space
        $usedPercentage = ($usedSpace / $totalSpace) * 100;
        $freePercentage = ($freeSpace / $totalSpace) * 100;

        // Optional: Check specific directories (example with storage directory)
        $storagePath = storage_path();
        $storageDirectorySize = $this->getDirectorySize($storagePath);

        // Threshold warning
        $threshold = 10; // 10% free space threshold
        $status = $freePercentage < $threshold ? 'Warning: Low Disk Space' : 'Disk Space Sufficient';

        return [
            'total_space' => $this->formatBytes($totalSpace),
            'used_space' => $this->formatBytes($usedSpace),
            'free_space' => $this->formatBytes($freeSpace),
            'used_percentage' => round($usedPercentage, 2) . '%',
            'free_percentage' => round($freePercentage, 2) . '%',
            'status' => $status,
            'directories' => [
                'storage_directory' => $this->formatBytes($storageDirectorySize)
            ]
        ];
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    private function getDirectorySize($directory)
    {
        $size = 0;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }





    function afficheAgent(Request $request)
    {
        $user = JWTAuth::user();
        $role = $user->role()->first();

        if ($role->id !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à accéder à cette ressource',
                'data' => []
            ], 403);
        }

        $agent = User::find($request->input('agentID'));

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => "L'utilisateur n'existe pas",
                'data' => [],
            ]);
        }

        $typeFac = [];
        if ($agent->type_facture_ids) {
            foreach ($agent->type_facture_ids as $id) {
                $type = TypesFactures::find($id);
                if ($type) {
                    $typeFac[] = ['values' => $type->id, 'label' => $type->typeName];
                }
            }
        }


        return response()->json([
            'success' => true, // Correction du succès à true, il semble qu'il soit erroné dans le code original
            'message' => "Agent récupéré avec succès",
            'data' => [
                'agent' => [
                    'email' => $agent->email,
                    'phone' => $agent->phone,
                    'name' => $agent->name,
                    'isActive' => $agent->isActive,
                    'typeFacture' => $typeFac
                ]
            ],
        ]);
    }
}
