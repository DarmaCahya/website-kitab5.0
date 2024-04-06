<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\AyatController;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::group(['middleware' => 'guest'], function () {
    Route::get('api/google', function () {
        return Socialite::driver('google')->redirect();
    });

    Route::get('api/google/callback', function () {
        try {
            $user = Socialite::driver('google')->user();
            $finduser = User::where('google_id', $user->id)->first();

            if ($finduser) {
                Auth::login($finduser);
                $token = $finduser->createToken('access_token')->plainTextToken;
                session(['access_token' => $token]);
                session(['user' => $finduser]);
                
                return redirect('/landingPage');
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'google_id' => $user->id,
                    'password' => Hash::make('password')
                ]);

                $newUser->google_id = $user->id;
                $newUser->save();

                $token = $newUser->createToken('access_token')->plainTextToken;
                session(['access_token' => $token]);
                session(['user' => $newUser]);

                Auth::login($newUser);
                return redirect('/landingPage');
            }
        } catch (\Throwable $th) {
            dd($th);
        }
    })->name('googleCallback');

    Route::post('/authentication', function (Request $request) {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $token = $user->createToken('token-name')->plainTextToken;
            session(['access_token' => $token]);
            session(['user' => $user]);
            return redirect()->route('landingPage');
        }

        return redirect()->back()->withErrors(['email' => 'Invalid email or password.']);
    })->name('authentication');

    Route::post('/register', function (Request $request) {
        if (session()->has('access_token')) {
            return redirect('/landingPage')->with('success', 'Anda sudah login!');
        }
        $authController = new AuthController();
        $response = $authController->register($request);
        $responseData = json_decode($response->getContent(), true);
        if ($responseData['message'] === 'Pendaftaran berhasil') {
            return redirect('/loginPage')->with('success', 'Registrasi berhasil!');
        } else {
            return $response;
        }
    })->name('register');

    Route::get('/landingPage', function () {
        $token = session('access_token');
        return view('landingPage');
    })->name('landingPage');
    
    Route::get('/loginPage', function () {
        if (session()->has('access_token')) {
            return redirect('/landingPage')->with('success', 'Anda sudah login!');
        }
        return view('loginPage');
    })->name('login');
    
});

Route::get('/logout', function (Request $request) {
    session()->forget('access_token');
    session()->forget('user');


    return redirect()->route('login')->with('success', 'Logout berhasil!');
})->name('logout');

Route::get('/login', function () {
    return view('login');
});
Route::get('/signUp', function () {
    return view('signUp');
});
Route::get('/changePass', function () {
    return view('changePass');
});
Route::get('/kitab', function () {
    return view('kitab');
});
Route::get('/scripture', function () {
    return view('scripture');
});
Route::get('/favorite', function () {
    return view('favorite');
});
Route::get('/contact', function () {
    return view('contact');
});

Route::get('/books/{book}', function (Request $request, $book) {
    $books = [
        [
            'id' => 1,
            'name' => 'al-quran',
            'api' => 'https://equran.id/api/v2/surat'
        ],
        [
            'id' => 2,
            'name' => 'bhagavad-gita',
            'api' => 'https://bhagavadgitaapi.in/chapters'
        ]
    ];

    $bookIndex = array_search($book, array_column($books, 'name'));

    if ($bookIndex === false) {
        return response()->json(['error' => 'Buku tidak ditemukan.'], 404);
    }
    
    if ($request->has('chapter')) {
        $chapter = $request->query('chapter');
        $apiUrl = $books[$bookIndex]['api'] . "/$chapter";
        
        $response = Http::get($apiUrl);

        if ($response->successful()) {
            $chapter = $response->json();

            $format_chapter = [
                'chapter_id' => $chapter['data']['nomor'],
                'chapter_name' => $chapter['data']['nama'],
                'translation' => $chapter['data']['arti'],
                'verses' => $chapter['data']['ayat'],
                'description' => $chapter['data']['deskripsi'],
            ];

            return response()->json($format_chapter);
        } else {
            // coba get dari database
            return response()->json(['error' => 'Gagal mengambil data chapter.'], $response->status());
        }
    } else {
        $response = Http::get($books[$bookIndex]['api']);
        if ($response->successful()) {
            $chapters = $response->json();

            $format_chapters = [];
            foreach ($chapters['data'] as $chapter) {
                $format_chapters[] = [
                    'id' => $chapter['nomor'],
                    'nama_latin' => $chapter['namaLatin']
                ];
            }

            return response()->json($format_chapters);
        } else {
            return response()->json(['error' => 'Gagal mengambil data chapter.'], $response->status());
        }
    }
});


Route::get('/search', function (Request $request) {
    $books = [
        [
            'id' => 1,
            'name' => 'al-quran',
            'api' => 'https://equran.id/api/v2/surat'
        ],
        [
            'id' => 2,
            'name' => 'bhagavad-gita',
            'api' => 'https://bhagavadgitaapi.in/slok'
        ]
    ];

    $chapter = [
        'id' => 1,
        'name' => 'al-fatihah',
        'book_id' => 1,
    ];

    $bookName = $request->query('book');
    $chapterNumber = $request->query('chapter');
    $verseNumber = $request->query('verse');

    $bookIndex = array_search($bookName, array_column($books, 'name'));
    if ($bookIndex === false) {
        return response()->json(['error' => 'Buku tidak ditemukan.'], 404);
    }

    $apiUrl = $books[$bookIndex]['api'] . "/" . $chapterNumber;
    $response = Http::get($apiUrl);
    if ($response->successful()) {
        $chapters = $response->json();
        
        if ($verseNumber === null) { // Jika tidak ada nomor ayat yang dicari
            $format_chapters = [
                'chapter_id' => $chapters['data']['nomor'],
                'chapter_name' => $chapters['data']['nama'],
                'translation' => $chapters['data']['arti'],
                'verses' => $chapters['data']['ayat'],
                'description' => $chapters['data']['deskripsi'],
            ];
            return response()->json($format_chapters);
        }

        $verse = null;
        foreach ($chapters['data']['ayat'] as $ayah) {
            if (isset($ayah['nomorAyat']) && $ayah['nomorAyat'] == $verseNumber) {
                $verse = $ayah;
                break;
            }
        }

        if ($verse) {
            $format_chapters = [
                'chapter_id' => $chapters['data']['nomor'],
                'chapter_name' => $chapters['data']['nama'],
                'translation' => $chapters['data']['arti'],
                'verse' => $verse,
                'description' => $chapters['data']['deskripsi'],
            ];
            return response()->json($format_chapters);
        } else {
            return response()->json(['error' => 'Ayat tidak ditemukan.'], 404);
        }
    } else {
        return response()->json(['error' => 'Gagal mengambil data chapter.'], $response->status());
    }
});

Route::group(['middleware' => 'auth'], function () {

    Route::group(['prefix' => 'client'], function () {
        Route::get('/', [ClientController::class, 'index'])->name('client.index');
        Route::get('/create', [ClientController::class, 'create'])->name('client.create');
        Route::post('/store', [ClientController::class, 'store'])->name('client.store');
        Route::get('/{id}/edit', [ClientController::class, 'edit'])->name('client.edit');
        Route::put('/{id}/update', [ClientController::class, 'update'])->name('client.update');
        Route::delete('/{id}/destroy', [ClientController::class, 'destroy'])->name('client.destroy');
        Route::get('/{id}', [ClientController::class, 'show'])->name('client.show');
    });
    
    Route::group(['prefix' => 'admin'], function () {
        Route::get('/', [AdminController::class, 'index'])->name('admin.index');
        Route::get('/create', [AdminController::class, 'create'])->name('admin.create');
        Route::post('/store', [AdminController::class, 'store'])->name('admin.store');
        Route::get('/{id}/edit', [AdminController::class, 'edit'])->name('admin.edit');
        Route::put('/{id}/update', [AdminController::class, 'update'])->name('admin.update');
        Route::delete('/{id}/destroy', [AdminController::class, 'destroy'])->name('admin.destroy');
        Route::get('/{id}', [AdminController::class, 'show'])->name('admin.show');
    });

});
