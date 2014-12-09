function validate(form)
{
	if( form.cfname.value == "" || form.cfemail.value == "" || form.cfcomments.value == "" ) 
   { 
	  document.getElementById('message').innerHTML = 'Please fill out name, email and a message.';
	  document.getElementById('message').style.display = 'block';
	  return false; 
   }
}