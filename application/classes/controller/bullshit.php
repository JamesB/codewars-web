<?php defined('SYSPATH') OR die('No direct access allowed.');

// ************************************************************
//
//  Copyright 2010 Department of Applied Mathematics (APPM) at the 
//		       University of Colorado at Boulder (UCB)
//
//  Revision History:
//  01/01/2011	jb		Updated file to have header
//  01/28/2011  jb    Added download capabilities for zip files within the /views/bullshit/codewrappers directory, put Java wrapper up
//
//  Confidential: Not for use or disclosure outside APPM-UCB without
//                        prior written consent.
//
// ***********************************************************


class Controller_Bullshit extends Controller_Template {
	public $template = 'bullshit/void';
  public $title = "";
	public function before() {
                parent::before();

                if(Auth::instance()->logged_in()) {
                        $this->user = Auth::instance()->get_user();
			//now you have access to user information stored in the database
			View::bind_global('user',$this->user);
                }

	}

  public function headers(){
    echo View::factory('bullshit/common/header')->set('title', $this->title);
  }
  public function footers(){
    echo View::factory('bullshit/common/footer');
  }

	public function action_index() {
	  $this->title = "Bullshit Home";
	  $this->headers();
	  echo View::factory("bullshit/index");
	  $this->footers();
	}
	
	private function download($dir, $file){
	  chdir($dir);
	  // if the filename is in the array
	  if(preg_match("/\.zip$/", $file)){
	    // and it exists
  	  if(file_exists("$file")){
        // and is readable
        if(is_readable("$file")){
          // get its size
          $size=filesize("$file");
          // open it for reading
          if($fp = @fopen("$file", 'r')){
            // send the headers
            header("Content-type: application/zip");
            header("Content-Length: $size");
            header("Content-Disposition: attachment; filename=\"$file\"");
            // send the file content
            fpassthru($fp);
            // close the file
            fclose($fp);
            // and quit
            exit;
          }
        }
      }
    }
	}
	
	public function action_wrappers($wrapperName = null){
	  if($wrapperName != null){
	    $this->download(dirname(__FILE__ ) . "/../../views/bullshit/codewrappers/", $wrapperName . "Wrapper.zip");
	  }
	  $this->title = "Bullshit Wrappers";
  	$this->headers();
	  echo View::factory("bullshit/wrappers");
	  $this->footers();
	}
	
	private function getUserBotId(){
  	$authentic=new Auth;
		if(!$authentic->logged_in()){
			$uid = 0;
		}
		else{
			$user = $authentic->get_user();
			$uid = $user->id;
    }
	  $bid = ORM::factory('server')
			->join('bots',array('servers.id'=>'sid'))
			->select('bots.id')
			->where(array(
				'servers.name'=>'bullshit',
				'uid'=>$uid,
			))
			->find();
		return $bid->id;
	}
	
	private function getLeaders(){
	  $recentGames = ORM::factory('bots')
			->select(array(
			  'idBots',
				'rank'
			))
			->orderby('rank','DESC')
			->limit("10")
			->find_all();
		$content = "<table><tr><th>Rank</th><th>Bot</th></tr>";
		foreach($recentGames AS $game){
		  $content .= "<tr>";
		  $content .= "<td>" . $game->rank . "</td>";
		  $content .= "<td>" . $game->idBots . "</td>";
      $content .= "</tr>";
		}
		$content .= "</table>";
	  return $content;
	}
	private function getScores(){
	  $recentGames = ORM::factory('bots')
			->select(array(
			  'idBots',
				'rank'
			))
			->where('idBots', $this->getUserBotId())
			->orderby('rank', 'DESC')
			->limit("10")
			->find_all();
		$content = "<table><tr><th>Rank</th><th>Bot</th></tr>";
		foreach($recentGames AS $game){
		  $content .= "<tr>";
		  $content .= "<td>" . $game->rank . "</td>";
		  $content .= "<td>" . $game->idBots . "</td>";
      $content .= "</tr>";
		}
		$content .= "</table>";
	  return $content;
	}
	public function action_scoreboards(){
	  $this->title = "Bullshit Scoreboards";
	  $this->headers();
	  $view = new View("bullshit/scoreboards");
	  $view->leaderboard = $this->getLeaders();
	  $view->scoreboard = $this->getScores();
	  echo $view;
	  $this->footers();
	}
	
	private function recentGamesIsWinner($winner, $bot){
	  if($winner == $bot){
	    return "<td class='winner'>" . $bot . "</td>";
	  }
	  return "<td>" . $bot . "</td>";
	}
	
	private function getRecentGames(){
	  $recentGames = ORM::factory('games')
			->select(array(
			  'idGames',
				'DatePlayed',
				'Bot1',
				'Bot2',
				'Bot3',
				'Bot4',
				'Winner'
			))
			->orderby('DatePlayed','DESC')
			->limit("10")
			->find_all();
		$content = "<table><tr><th>Game Id</th><th>Bot 1</th><th>Bot 2</th><th>Bot 3</th><th>Bot 4</th></tr>";
		foreach($recentGames AS $game){
		  $content .= "<tr>";
		  $content .= "<td>" . $game->idGames . "</td>";
      $content .= $this->recentGamesIsWinner($game->Winner, $game->Bot1);
      $content .= $this->recentGamesIsWinner($game->Winner, $game->Bot2);
      $content .= $this->recentGamesIsWinner($game->Winner, $game->Bot3);
      $content .= $this->recentGamesIsWinner($game->Winner, $game->Bot4);
		  $content .= "</tr>";
		}
		$content .= "</table>";
	  return $content;
	}
	private function getMyRecentGames(){
	  $recentGames = ORM::factory('games')
			->select(array(
			  'idGames',
				'DatePlayed',
				'Bot1',
				'Bot2',
				'Bot3',
				'Bot4',
				'Winner'
			))
			->where(array(
			  "Bot1" => $this->getUserBotId(),
			  "Bot2" => $this->getUserBotId(),
			  "Bot3" => $this->getUserBotId(),
			  "Bot4" => $this->getUserBotId()
			))
			->orderby('DatePlayed','DESC')
			->limit("10")
			->find_all();
		$content = "<table><tr><th>Game Id</th><th>Bot 1</th><th>Bot 2</th><th>Bot 3</th><th>Bot 4</th></tr>";
		foreach($recentGames AS $game){
		  $content .= "<tr>";
		  $content .= "<td>" . $game->idGames . "</td>";
      $content .= $this->recentGamesIsWinner($game->Winner, $game->Bot1);
      $content .= $this->recentGamesIsWinner($game->Winner, $game->Bot2);
      $content .= $this->recentGamesIsWinner($game->Winner, $game->Bot3);
      $content .= $this->recentGamesIsWinner($game->Winner, $game->Bot4);
		  $content .= "</tr>";
		}
		$content .= "</table>";
	  return $content;
	}
	public function action_recent(){
	  $this->title = "Bullshit Recent Games";
	  $this->headers();
	  $view = new View("bullshit/recent");
	  $view->recentGames = $this->getRecentGames();
	  $view->myRecentGames = $this->getMyRecentGames();
	  echo $view;
	  $this->footers();
	}
}
