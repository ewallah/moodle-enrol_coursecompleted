<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings for component 'enrol_coursecompleted', language 'es'.
 *
 * @package   enrol_coursecompleted
 * @copyright eWallah (www.eWallah.net)
 * @author    Renaat Debleu <info@eWallah.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['aftercourse'] = 'Tras completar el curso: {$a}';
$string['assignrole'] = 'Asignar rol';
$string['compcourse'] = 'Curso completado';
$string['compcourse_help'] = 'Qué curso debe completarse.';
$string['confirmbulkdeleteenrolment'] = '¿Seguro que quieres eliminar estas matriculaciones de usuarios?';
$string['confirmbulkediteenrolment'] = '¿Seguro que quieres cambiar estas matriculaciones de usuarios?';
$string['coursecompleted:config'] = 'Configurar instancias de matriculación por curso completado';
$string['coursecompleted:enrolpast'] = 'Matricular a usuarios que completaron cursos en el pasado';
$string['coursecompleted:manage'] = 'Gestionar usuarios matriculados';
$string['coursecompleted:unenrol'] = 'Desmatricular usuarios del curso';
$string['coursecompleted:unenrolself'] = 'Desmatricularse del curso';
$string['customwelcome'] = 'Mensaje de bienvenida personalizado';
$string['customwelcome_help'] = 'Se puede añadir un mensaje de bienvenida personalizado como texto plano o en formato Moodle-auto, incluyendo etiquetas HTML y etiquetas multi-idioma.

Se pueden incluir los siguientes marcadores en el mensaje:

* Nombre del curso {$a->coursename}
* Nombre del curso completado {$a->completed}
* Enlace a la página de perfil del usuario {$a->profileurl}
* Correo del usuario {$a->email}
* Nombre completo del usuario {$a->fullname}';
$string['defaultrole'] = 'Asignación de rol por defecto';
$string['defaultrole_desc'] = 'Selecciona el rol a asignar a los usuarios cuando se matriculan.';
$string['deleteselectedusers'] = 'Eliminar las matriculaciones seleccionadas al completar el curso';
$string['editselectedusers'] = 'Editar las matriculaciones seleccionadas al completar el curso';
$string['editusers'] = 'Cambiar matriculaciones de usuarios';
$string['enroldate'] = 'Fecha de matriculación';
$string['enroldate_help'] = 'Si se activa, los usuarios se matricularán automáticamente en un momento concreto en el futuro.';
$string['enrolenddate'] = 'Fecha de fin';
$string['enrolenddate_help'] = 'Si se activa, los usuarios solo se matricularán automáticamente hasta esta fecha. Todas las finalizaciones de curso posteriores a esta fecha se ignorarán.';
$string['enrolenddaterror'] = 'La fecha de fin de matriculación no puede ser anterior a la de inicio.';
$string['enrolperiod'] = 'Duración de la matriculación';
$string['enrolperiod_desc'] = 'Duración por defecto de la validez de la matriculación. Si se pone a cero, la duración será ilimitada por defecto.';
$string['enrolperiod_help'] = 'Tiempo durante el cual la matriculación es válida, empezando en el momento en que el usuario se matricula. Si se desactiva, la duración será ilimitada.';
$string['enrolstartdate'] = 'Fecha de inicio';
$string['enrolstartdate_help'] = 'Si se activa, los usuarios solo se matricularán automáticamente a partir de esta fecha. Todas las finalizaciones de curso anteriores a esta fecha se ignorarán.';
$string['expiredaction'] = 'Acción al caducar la matriculación';
$string['expiredaction_help'] = 'Selecciona la acción a realizar cuando caduca la matriculación de un usuario. Ten en cuenta que algunos datos y ajustes del usuario se eliminan cuando se le desmatricula.';
$string['group'] = 'Mantener grupo';
$string['group_help'] = 'Intentar añadir a los usuarios a un grupo con el mismo nombre';
$string['keepgroup'] = 'Ajuste por defecto de mantener grupo';
$string['keepgroup_help'] = 'Por defecto, intentar añadir a los usuarios a un grupo con el mismo nombre';
$string['pluginname'] = 'Matriculación por curso completado';
$string['pluginname_desc'] = 'El plugin de matriculación por curso completado da acceso a cursos al completar un curso.';
$string['privacy:metadata'] = 'El plugin de matriculación por curso completado no almacena ningún dato personal.';
$string['processexpirationstask'] = 'Tarea de caducidad de la matriculación por curso completado';
$string['status'] = 'Habilitado';
$string['status_desc'] = 'Permitir la matriculación por curso completado por defecto.';
$string['status_help'] = 'Este ajuste determina si la matriculación por curso completado está habilitada.';
$string['status_link'] = 'enrol/coursecompleted';
$string['svglearnpath'] = 'Mostrar itinerario de aprendizaje';
$string['svglearnpath_help'] = 'Mostrar el (posible) itinerario de aprendizaje usando iconos svg.';
$string['tryunenrol'] = 'Desmatricular al usuario del curso completado.';
$string['tryunenrol_help'] = 'Intentar auto-desmatricular al usuario del curso completado.
Si el usuario se matriculó con un método que permite la auto-desmatriculación, este plugin intentará auto-desmatricularlo.';
$string['unenrolusers'] = 'Desmatricular usuarios';
$string['uponcompleting'] = 'Al completar el curso {$a}';
$string['usersenrolled'] = '{$a} usuarios matriculados';
$string['welcome'] = 'Enviar mensaje de bienvenida al curso';
$string['welcome_help'] = 'Cuando un usuario se matricula en un curso al completar otro, se le puede enviar un correo de bienvenida.';
$string['welcometocourse'] = '¡Bienvenido/a a {$a->coursename}!

¡Enhorabuena!

Tras completar con éxito {$a->completed}, ahora estás matriculado/a automáticamente en el siguiente curso: {$a->coursename}.';
$string['willbeenrolled'] = 'Se te matriculará en este curso cuando completes el curso {$a}';
$string['groupfilter'] = 'Restringir a grupos';
$string['groupfilter_help'] = 'Solo los estudiantes que pertenezcan al menos a uno de los grupos seleccionados del curso completado se matricularán automáticamente. Déjalo vacío para matricular a todos los estudiantes que completen el curso. Elige primero el curso completado: la lista carga los grupos de ese curso.';
$string['groupfilter_all'] = 'Todos los estudiantes (sin restricción de grupo)';
