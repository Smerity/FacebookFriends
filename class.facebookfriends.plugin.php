<?php if (!defined('APPLICATION')) exit();

$PluginInfo['FacebookFriends'] = array(
   'Name' => 'FacebookFriends',
   'Description' => "Shows the real name of any of your Facebook friends on the Vanilla Forum if you log in with the Facebook plugin",
   'Version' => '0.1',
   'RequiredApplications' => array('Vanilla' => '2.0.17'),
   'RequiredTheme' => FALSE,
   'RequiredPlugins' => FALSE,
   'HasLocale' => FALSE,
   'RegisterPermissions' => FALSE,
   'Author' => 'Smerity',
   'AuthorEmail' => 'smerity@smerity.com',
   'AuthorUrl' => 'http://www.smerity.com/'
);

class FacebookFriendsPlugin extends Gdn_Plugin {

   // Where we'll cache the current user's list of friends
   public $friendlist = NULL;

   /**
    * Return an array {fb_id => name} (empty array if empty) or return NULL on error
    */
   public function GetFriends($AccessToken) {
      // TODO: This currently gets a list of all friends -- we can also only get friends in the application itself
      // Getting all friends is larger but means there's no lag in seeing the name if a new friend joins the forum
      $Url = "https://graph.facebook.com/fql?q=" . urlencode("SELECT uid, name FROM user WHERE uid IN (SELECT uid2 FROM friend WHERE uid1 = me())") . "&access_token=$AccessToken";
      $C = curl_init();
      curl_setopt($C, CURLOPT_RETURNTRANSFER, TRUE);
      // TODO: This follows what is done in the Facebook plugin but this is insecure
      curl_setopt($C, CURLOPT_SSL_VERIFYPEER, FALSE);
      curl_setopt($C, CURLOPT_URL, $Url);
      $Contents = curl_exec($C);
      if (curl_errno($C) != 0) {
         return NULL;
      }
      $RawFriends = json_decode($Contents, TRUE);
      $Friends = Array();
      // Convert to the correct format (key=uid, value=name)
      foreach($RawFriends["data"] as $friend) {
         $Friends[$friend["uid"]] = $friend["name"];
      }
      return $Friends;
   }

   /**
    * This fires once per page load and is where we'll prepare the list of Facebook friends (if logged in).
    */
   public function __construct() {
      $AccessToken = FacebookPlugin::AccessToken();
      // TODO: The Facebook plugin should be modified to store the token in the user's attributes
      // If it isn't, Facebook users who log in with normal user/pass won't have the personalisation
      //$AccessToken = Gdn::Session()->GetAttribute('Facebook.Token', NULL);
      $this->friendlist = NULL;
      // If the user has an access token, get their friends -- first check cache, otherwise make URL request
      if ($AccessToken) {
         $cache_key = "fb_friends_for_" . $AccessToken;
         $contents = Gdn::Cache()->Get($cache_key);
         if ($contents === Gdn_Cache::CACHEOP_FAILURE || $contents === FALSE) {
            $this->friendlist = $this->GetFriends($AccessToken);
            // TODO: Make the cache duration flexible through the site's config file
            $save_cache = Gdn::Cache()->Store($cache_key, $this->friendlist, array(Gdn_Cache::FEATURE_EXPIRY => 30*60));
         } else {
            $this->friendlist = $contents;
         }
      }
   }

   /**
    * Insert the real names next to the people's names
    */
   public function DiscussionController_AuthorInfo_Handler(&$Sender) {
      $FB = Gdn::Session()->GetAttribute('Facebook.Profile', NULL);
      // If the author has a Facebook profile and a friendlist, check if the author is their friend
      if($FB && $this->friendlist) {
         // Check if the author has a Facebook profile
         $FriendFB = Gdn::UserModel()->GetAttribute($Sender->EventArguments['Author']->UserID, 'Facebook.Profile');
         if(!$FriendFB) return;
         $fkey = $FriendFB["id"];
         // If the author ID is in list of friends, then add their name
         if(array_key_exists($fkey, $this->friendlist)) {
	    $UserName = $this->friendlist[$fkey];
            // TODO: Make this flexible so that it can be styled or modified easily by users
            echo '<b>' . $UserName . '</b>';
         }
      }
   }

}
