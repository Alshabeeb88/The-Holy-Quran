//CHANGE SURAH
function changeSurah(surah)
{
location=''+surah;
}

function playAudio(id,file) {
  var x = document.getElementById(id);
  var b = document.getElementById('b_'+id);
  var player = document.getElementById('player');

  if (x.style.display === "block") {
    x.style.display = "none";
    b.innerHTML = '<i class="fas fa-play"></i>';
    document.getElementById(id).innerHTML = '';
  } else {
    x.style.display = "block";
    b.innerHTML = '<i class="fas fa-stop"></i>';
    document.getElementById(id).innerHTML = '<audio id="player" class="mt-2" controls="controls" src="' + file + '" autoplay></audio>';
  }

}

function changeurl(id){ location=id; }

function changesound(ID) {
  var sound_id = document.getElementById("sound");
  sound_id.innerHTML = '<audio controls autoplay><source src="'+ID+'" type="audio/mpeg">Your browser does not support the audio element.</audio><p><a href="'+ID+'">download</a></p>';
}
