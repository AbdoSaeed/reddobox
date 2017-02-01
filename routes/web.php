<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\User;
use App\SocialAccount;
use App\SocialFriend as Friend;
use App\Rate;
use App\Project;
use App\projectRate;
use App\Notification;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Auth::routes();

Route::get('/home', 'HomeController@index');

Route::post('/auth', function (Request $request, User $user) {
    if (Auth::attempt(['email' => $request['email'], 'password' => $request['password']])) {
    	return redirect()->route('dashboard');
    }
});

Route::get('/dashboard', function (Request $request) {
	$user = new User();
	$friends = $user->getFbFriends();
	$result = '';
	if($request['email']) {
		$email = $request['email'];
		$check = User::whereEmail($email)->first();
		if(count($check)>0) {
			$result = $check;
		}
	}
	$user = new User();
	return view('dashboard', compact(['friends', 'result', 'user']));
})->name('dashboard');

Route::get('/redirect', 'SocialAuthController@redirect');
Route::get('/callback', 'SocialAuthController@callback');


Route::post('friend', function (Request $request) {
	if(count(Friend::where('friend_provider_id', $request['id'])->whereUserId(Auth::id())->get()) == 0) {
	    $friend = new Friend([
	        'friend_provider_id' => $request['id'],
	    ]);
	    $friend->user()->associate(Auth::user());
	    $friend->save();
	}
});

Route::group(['prefix' => 'rate', 'middleware' => 'auth'], function () {
	Route::get('get_traits/{cat}', function($cat) {
	    return ['traits' => App\RateTrait::whereType($cat)->get()];
	});
	Route::post('/social', function (Request $request) {
		$trait = $request['trait_id'];
		$check = Rate::whereFromId(Auth::id())->whereUserId($request['id'])
						->whereCategory('social')
						->whereRateTraitId($trait)->first();
		if(count($check) > 0) {
			$check->rate = $request['rate'];
			$check->save();
			// $text = 'Someone rated you socially!';
			// $url = '/profile/' . $request['id'];
			// $user = User::whereId($request['id'])->first();
			// $user->newNotification($request['id'], Auth::id(), $text, $url);	
			return ['check' => true];
		}else{
			$rate = new Rate([
				'from_id' => Auth::user()->id,
				'category' => 'social',
				'review' => $request['review'],
				'rate_trait_id' => $trait,
				'rate' => $request['rate'],
			]);
			$user = User::whereId($request['id'])->first();
			$rate->user()->associate($user);
			$rate->save();
			return ['check' => true];
		}
	});
	Route::get('/social/{id}', function (Request $request, $id) {
		// $columns = Schema::getColumnListing('rates');
		// for ($i=0; $i < count(Schema::getColumnListing('rates'))-1; $i++) { 
		// 	if($columns[$i] == 'id' || $columns[$i] == 'user_id' || $columns[$i] == 'from_id' || $columns[$i] == 'review' || $columns[$i] == 'created_at' || $columns[$i] == 'updated_at') {
		// 		unset($columns[$i]);
		// 	}
		// }
		if($id == Auth::id()) {
			return;
		}
		$user = User::find($id);
		return view('rate.social', compact('user', 'traits'));
	});

	Route::get('/personal/{id}', function (Request $request, $id) {
		// $columns = Schema::getColumnListing('rates');
		// for ($i=0; $i < count(Schema::getColumnListing('rates'))-1; $i++) { 
		// 	if($columns[$i] == 'id' || $columns[$i] == 'user_id' || $columns[$i] == 'from_id' || $columns[$i] == 'review' || $columns[$i] == 'created_at' || $columns[$i] == 'updated_at') {
		// 		unset($columns[$i]);
		// 	}
		// }
		if($id == Auth::id()) {
			return;
		}
		$user = User::find($id);
		return view('rate.personal', compact('user'));
	});
	Route::post('get', function(Request $request) {
		$from_id = $request['from_id'];
		$user_id = $request['user_id'];
		$category = $request['category'];
		$traits = App\RateTrait::whereType($category)->get();
		$rates = [];
		foreach ($traits as $trait) {
			$tmp = Rate::whereCategory($category)->whereFromId($from_id)->whereUserId($user_id)->whereRateTraitId($trait->id)->first();
			if(count($tmp) > 0) {
				array_push($rates, $tmp);
			}
		}
	    return ['rate' => $rates];
	});
	Route::post('/personal', function (Request $request) {
		$trait = $request['trait_id'];
		$check = Rate::whereFromId(Auth::id())->whereUserId($request['id'])
						->whereCategory('personal')
						->whereRateTraitId($trait)->first();
		if(count($check) > 0) {
			$check->rate = $request['rate'];
			$check->save();
			return ['check' => true];
		}else{
			$rate = new Rate([
				'from_id' => Auth::user()->id,
				'category' => 'personal',
				'review' => $request['review'],
				'rate_trait_id' => $trait,
				'rate' => $request['rate'],
			]);
			$user = User::whereId($request['id'])->first();
			$rate->user()->associate($user);
			$rate->save();
			return ['check' => true];
		}
	});

	Route::group(['prefix' => 'professional/{id}'], function() {
	    Route::get('/', function($id) {
	    	$user = new User();
			return view('rate.professional', compact('id', 'user'));
	    });
	    Route::post('/', function(Request $request, $id) {
	    	$project = new Project([
	    		'type' => $request['details']['type'],
	    		'title' => $request['details']['title'],
	    		'description' => $request['details']['desc'],
	    	]);
	    	$project->user()->associate(User::find($id));
	    	$project->save();
	    })->name('new_project');
	    Route::post('/rate/{project_id}', function ($user_id, $project_id) {
	    	$check = projectRate::whereUserId($user_id)->whereProjectId($project_id)->get();
	    	if(count($check)>0) {
	    		return ['check' => false];
	    	}else{
		    	$rate = new projectRate();
		    	$rate->user()->associate(User::find($user_id));
		    	$rate->project()->associate(User::find($project_id));
		    	$rate->save();
	    		return ['check' => true];
	    	}
	    });
	});

});

Route::group(['prefix' => 'invite'], function() {
    //
    Route::get('{project_id}', function($project_id) {
        //
        $project = Project::find($project_id);
        $users = User::all();
        return view('invite', compact('project', 'users'));
    });
	Route::post('toggle/{id}', function($id, Request $request) {
	    //
	    Auth::user()->inviteToggle(User::find($id), $request['projectID']);
	});
	Route::get('check/{user_id}/{project_id}', function($user_id, $project_id) {
	    //
	    $check = Auth::user()->invited($user_id, $project_id);
	    if(count($check) > 0) {
	    	return ['check' => true];
	    }else{
	    	return ['check' => false];
	    }
	});
});


Route::group(['prefix' => 'profile', 'middleware' => 'auth'], function() {
	Route::post('/{id}', function(Request $request) {
	    if($request->hasFile('image')) {
	    	$img = Image::make($request->file('image'));

			// resize image instance
			$img->resize(200, 200);

			// save image in desired format
			$name = time() . '.' . $request->file('image')->getClientOriginalExtension();
			$img->save(public_path('uploads/images/' . $name));

			$user = Auth::user();
			$user->avatar = $name;
			$user->save();
			return redirect()->back();
	    }
	});
    Route::get('{id}',[
		'uses' => 'ProfileController@index'
    ])->name('profileRoute');
});

Route::group(['prefix' => 'new', 'middleware' => 'auth'], function() {
	Route::post('friendRequest/{id}', function($id, Request $request) {
	    Auth::user()->befriend(User::find($id));
	    return ['check' => true];
	});
	Route::post('deleteRequest/{id}', function($id, Request $request) {
	    Auth::user()->unfriend(User::find($id));
	    return ['check' => true];
	});
	Route::post('checkIfFriend/{id}', function($id) {
		if (Auth::user()->isFriendWith(User::find($id))) {
			return ['check' => true];
		}else{
			return ['check' => false];
		}
	});
	Route::post('hasSentFriendRequestTo/{id}', function ($id) {
		if(Auth::user()->hasSentFriendRequestTo(User::find($id))) {
			return ['check' => true];
		}else{
			return ['check' => false];
		}
	});
	Route::post('hasFriendRequestFrom/{id}', function ($id) {
		if(Auth::user()->hasFriendRequestFrom(User::find($id))) {
			return ['check' => true];
		}else{
			return ['check' => false];
		}
	});
	Route::post('acceptFriendRequest/{id}', function ($id) {
		if(Auth::user()->acceptFriendRequest(User::find($id))) {
			return ['check' => true];
		}else{
			return ['check' => false];
		}
	});
	Route::post('block/{id}', function ($id) {
		if(Auth::user()->blockFriend(User::find($id))) {
			return ['check' => true];
		}else{
			return ['check' => false];
		}
	});
	Route::post('unblock/{id}', function ($id) {
		if(Auth::user()->unblockFriend(User::find($id))) {
			return ['check' => true];
		}else{
			return ['check' => false];
		}
	});
});
Route::get('/logout', function () {
	Auth::logout();
	return redirect()->route('welcome');
});
Route::post('notify', function(Request $request) {
	$user_id = $request['user_id'];
	$from_id = $request['from_id'];
	$text = $request['text'];
	$user = User::find($user_id);
	$url = $request['url'];
	$user->newNotification($user_id, $from_id, $text, $url);
});

Route::group(['prefix' => 'my'], function() {
	Route::get('box', function(User $user) {
	    return view('my_box', compact('user'));
	});
});

Route::get('getFriends', function() {
    //
    return ['friends' => Auth::user()->getFriends()];
});

Route::get('get/friendsRequests', function() {
    //
    return ['requests' => Auth::user()->getFriendsRequests()];
});
Route::get('get/notifications', function() {
    //
    return ['notifications' => Auth::user()->notifications()->get()];
});

Route::get('notificationsCount', function() {
    //
    return ['x' => count(Auth::user()->notifications()->whereState(true)->get())];
});
Route::get('get/invitations', function() {
    //
    return ['invitations' => Auth::user()->invitations()->get()];
});

Route::post('readNotification', function(Request $request) {
    //
	if($request['id'] !== null) {
	    $tmp = Notification::find($request['id']);
	    $tmp->state = 0;
	    $tmp->save();
	    return $tmp;
	}
	if($request['text'] !== null) {
	    $tmp = Notification::whereUserId(Auth::id())->whereText($request['text'])->get();
	    foreach ($tmp as $key) {
		    $key->state = 0;
		    $key->save();
	    }
	    return $request['text'];
	}
	if($request['from_id'] !== null) {
		$id = $request['from_id'];
		$tmp = Notification::where('from_id',$id)->whereUserId(Auth::id())->whereState(1)->get();
		foreach ($tmp as $key) {
			$key->state = 0;
			$key->save();
		}
		return $tmp;
	}
});

Route::post('laterNotification', function(Request $request) {
    //
	if($request['id'] !== null) {
	    $tmp = Notification::find($request['id']);
	    $tmp->later = 1;
	    $tmp->save();
	    return $tmp;
	}
});


Route::post('get/users', function(Request $request) {
    //
    $query = $request['search'];
    if(strlen($query) > 2) {
	    $check = User::where('email', 'Like', '%' . $query . '%')
	    ->orWhere('full_name', 'Like', '%' . $query . '%')->get();
	    function check($x) {
	    	if(count($x) > 0) {
	    		return true;
	    	}else{
	    		return false;
	    	}
	    }
		return ['users' => $check];
    }else{
    	return ['users' => []];
    }
});

Route::post('get/rates', function(Request $request) {
    //
	$socials = User::find($request['id'])->rates()->whereCategory('social')->get();
	$personals = User::find($request['id'])->rates()->whereCategory('personal')->get();
	return ['socials' => $socials, 'personals' => $personals];
});

Route::get('blocks/{id}', function() {
    //
    $user = new User();
    return view('blocks', compact('user'));
});

Route::get('seed/traits', function() {
    //
	$trait = new App\RateTrait(['name' => 'Active', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Honest', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Smart', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Organized', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Optimistic', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Open minded', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Polite', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Courage', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'kind', 'type' => 'personal']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Sense of humor', 'type' => 'personal']);
	$trait->save();

	$trait = new App\RateTrait(['name' => 'Socially Active', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Good listener', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Initiative', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Leader', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Caring', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Friendly', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Chatty', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Relations builder', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Helpful', 'type' => 'social']);
	$trait->save();
	$trait = new App\RateTrait(['name' => 'Cooperative', 'type' => 'social']);
	$trait->save();
});