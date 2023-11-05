
$(function() {
    var Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000
    });
});


function alertError(texto){
    Swal.fire({
        title: 'Error',
        text: texto,
        icon: 'error',
        showConfirmButton: false,
        timer: 1500,
        timerProgressBar: true
    });
}


function alertSuccessWithFunction(titulo, texto, nombrefuncion) {
    Swal.fire({
      title: titulo,
      text: texto,
      icon: 'success',
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true
    }).then(() => {
      if (nombrefuncion && typeof nombrefuncion === 'function') {
        nombrefuncion();
      }
    });
}


function alertErrorWithFunction(titulo, texto, nombrefuncion) {
    Swal.fire({
      title: titulo,
      text: texto,
      icon: 'error',
      showConfirmButton: false,
      timer: 1500,
      timerProgressBar: true
    }).then(() => {
      if (nombrefuncion && typeof nombrefuncion === 'function') {
        nombrefuncion();
      }
    });
}