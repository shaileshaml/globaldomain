<?php

/**
 * globaldomain
 *
 * Plugin to add a global Global Domain 
 *
 * @version 2.0.9
 * @license GNU GPLv3+
 * @author Shailesh Mistry
 * @email id : mistry.dlm@gmail.com
 */
class globaldomain extends rcube_plugin
{
	private $readonly;
	private $groups;
	private $name;
	private $user_id;
	private $user_name;
	private $host = 'localhost';

	public function init()
	{
		if(!isset($_SESSION['sessiondomain']))
			$_SESSION['sessiondomain']='';
		$this->add_hook('addressbooks_list', array($this, 'sessiondomain_sources'));
		$this->add_hook('addressbook_get', array($this, 'get_sessiondomain'));
		$this->add_hook('message_outgoing_headers', array($this, 'validate_global_domain')); 
	}

	public function sessiondomain_sources($args)
	{	
		$rcmail = rcmail::get_instance();
		
		$smusername=strtolower($rcmail->user->get_username());
		$smadminuser= strtolower($rcmail->config->get('gglobaldomain_admin'));
		if(!isset($_SESSION['sessiondomain']))
			$_SESSION['sessiondomain']='%d,example.com';
			
		if(strpos($smadminuser,$smusername,0)!==false)
			$_SESSION['sessiondomain']='all';
		elseif($_SESSION['sessiondomain']=='') 
		{			
			$query = $rcmail->db->query(
				"SELECT domains FROM session_domain WHERE 				
				username=? and is_active = 1 ",$smusername);
			$result = $rcmail->db->fetch_assoc($query);
			if ($result)
				$_SESSION['sessiondomain']=$result['domains'];
			else
				$_SESSION['sessiondomain']='%d';
			
		}
		$_SESSION['sessiondomain']=$_SESSION['sessiondomain'];
		$_SESSION['sessiondomain']=str_replace(' ', '', $_SESSION['sessiondomain']);
		$_SESSION['sessiondomain']=str_replace('%d', $rcmail->user->get_username('domain'), $_SESSION['sessiondomain']);

		return $args;
	}

	public function get_sessiondomain($args)
	{	
		
		/*$rcmail = rcmail::get_instance();
		if(!isset($_SESSION['sessiondomain']))
			$_SESSION['sessiondomain']=null;
		if($_SESSION['sessiondomain']==null || $_SESSION['sessiondomain']=='') 
		{
			
			$query = $rcmail->db->query(
				"SELECT domains FROM session_domain WHERE 				
				username=? and is_active = 1 ",
				$args['user']);
			$result = $rcmail->db->fetch_assoc($query);
			$_SESSION['sessiondomain']=$result['domains'];
		}
		
		$rcmail->output->show_message('Login failed. by admin.', 'warning');
		$rcmail->output->set_env('task', 'login');
		$rcmail->output->send('login');
		exit;*/
		return $args;
	}

	private function _is_readonly()
	{
		$rcmail = rcmail::get_instance();

		if (!$rcmail->config->get('globaldomain_readonly'))
			return false;

		if ($admin = $rcmail->config->get('globaldomain_admin')) {
			if (!is_array($admin)) $admin = array($admin);

			foreach ($admin as $user) {
				if (strpos($user, '/') == 0 && substr($user, -1) == '/') {
					if (preg_match($user, $_SESSION['username']))
						return false;
				}
				elseif ($user == $_SESSION['username'])
					return false;
			}
		}

		return true;
	}

	public function validate_global_domain($args)
	{
		$rcmail = rcmail::get_instance();			
		if ($_SESSION['sessiondomain']!=null ) 
		{
			if($_SESSION['sessiondomain']!='all')
			{
				$mailtosm = rcmail_email_input_format($args['headers']['To'], true);
				$mailccsm = rcmail_email_input_format($args['headers']['Cc'], true);
				$mailbccsm = rcmail_email_input_format($args['headers']['Bcc'], true);
	
				if(!empty($mailtosm))
				{
					$val="";$val1="";$temps= explode(",", $mailtosm);
					$isinvalid=false;
					for($i=0;$i<count($temps);$i++)
					{
						if(strpos($temps[$i],'@',0)>0)
						$val1 = substr($temps[$i],strpos($temps[$i],'@',0)+1);
						if(! in_array($val1,explode(",", $_SESSION['sessiondomain'])))
							{$isinvalid=true;break;}
					}
					if($isinvalid)
					{
						$args['message']='smtprecipientserror';
						$args['abort']=true;
						$rcmail->output->show_message($args['message'] , 'error');
						$rcmail->output->send('iframe');
						return $args;
					}
				}
		
				if(!empty($mailccsm))
				{
					$val="";$val1="";$temps= explode(",", $mailccsm);
					$isinvalid=false;
					for($i=0;$i<count($temps);$i++)
					{
						if(strpos($temps[$i],'@',0)>0)
						$val1 = substr($temps[$i],strpos($temps[$i],'@',0)+1);
						if(! in_array($val1,explode(",", $_SESSION['sessiondomain'])))
							{$isinvalid=true;break;}
					}
					if($isinvalid)
					{
						$args['message']='smtprecipientserror';
						$args['abort']=true;
						$rcmail->output->show_message($args['message'] , 'error');
						$rcmail->output->send('iframe');
						return $args;
					}
				}
		
				if(!empty($mailbccsm))
				{
					$val="";$val1="";$temps= explode(",", $mailbccsm);
					$isinvalid=false;
					for($i=0;$i<count($temps);$i++)
					{
						if(strpos($temps[$i],'@',0)>0)
						$val1 = substr($temps[$i],strpos($temps[$i],'@',0)+1);
						if(! in_array($val1,explode(",", $_SESSION['sessiondomain'])))
							{$isinvalid=true;break;}
					}
					if($isinvalid)
					{
						$args['message']='smtprecipientserror'	;
						$args['abort']=true;
						$rcmail->output->show_message($args['message'] , 'error');
						$rcmail->output->send('iframe');
						return $args;
					}
				}
		
		
			}
		}
		else
		{
			$args['message']='this sesion variable not set';
			$args['abort']=true;
			return $args;
		}
		//$args['message']='The reason why the message was not sent which will be shown to the user<br>Test' ;
		$args['abort']=true;
		return $args;
	}

	function rcmail_email_input_format($mailto, $count=false, $check=true)
	{
		global $RCMAIL, $EMAIL_FORMAT_ERROR, $RECIPIENT_COUNT;

		// simplified email regexp, supporting quoted local part
		$email_regexp = '(\S+|("[^"]+"))@\S+';

		$delim   = trim($RCMAIL->config->get('recipients_separator', ','));
		$regexp  = array("/[,;$delim]\s*[\r\n]+/", '/[\r\n]+/', "/[,;$delim]\s*\$/m", '/;/', '/(\S{1})(<'.$email_regexp.'>)/U');
		$replace = array($delim.' ', ', ', '', $delim, '\\1 \\2');

		// replace new lines and strip ending ', ', make address input more valid
		$mailto = trim(preg_replace($regexp, $replace, $mailto));
		$items  = rcube_utils::explode_quoted_string($delim, $mailto);
		$result = array();

		foreach ($items as $item) {
			$item = trim($item);
			// address in brackets without name (do nothing)
			if (preg_match('/^<'.$email_regexp.'>$/', $item)) {
				$item     = rcube_utils::idn_to_ascii(trim($item, '<>'));
				$result[] = $item;
			}
			// address without brackets and without name (add brackets)
			else if (preg_match('/^'.$email_regexp.'$/', $item)) {
				$item     = rcube_utils::idn_to_ascii($item);
				$result[] = $item;
			}
			// address with name (handle name)
			else if (preg_match('/<*'.$email_regexp.'>*$/', $item, $matches)) {
				$address = $matches[0];
				$name    = trim(str_replace($address, '', $item));
				if ($name[0] == '"' && $name[count($name)-1] == '"') {
					$name = substr($name, 1, -1);
				}
				$name     = stripcslashes($name);
				$address  = rcube_utils::idn_to_ascii(trim($address, '<>'));
				$result[] = format_email_recipient($address, $name);
				$item     = $address;
			}

			// check address format
			$item = trim($item, '<>');
			if ($item && $check && !rcube_utils::check_email($item)) {
				$EMAIL_FORMAT_ERROR = $item;
				return;
			}
		}

		if ($count) {
			$RECIPIENT_COUNT += count($result);
		}

		return implode(', ', $result);
	}
}

?>