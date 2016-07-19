<?php
// This file is part of the Assessment plugin for Moodle
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
 * @package    mod_assessment
 * @copyright  2016 CIE
 * @author     Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class mod_assessment_renderer extends plugin_renderer_base {

    private $assessment;
    private $makepdf;

    public function set_assessment($assessment) {
        $this->assessment = $assessment;
        if (isset($this->assessment->makepdf)) {
            $this->makepdf = true;
        } else {
            $this->makepdf = false;
        }
    }

    public function user_search($searchword) {
        $template = '
            <form class="assessmentuserform" method="get"
            action="{{url}}"
            role="search">
                <div>
                    <label for="adminsearchquery" class="accesshide">Search in settings</label>
                    <input id="adminsearchquery" type="text" name="query" value="{{searchword}}">
                    <input type="submit" value="{{searchstring}}">
                    <input type="hidden" name="id" value="{{cmid}}">
                </div>
            </form>';

        $values = array(
            '{{url}}',
            '{{searchword}}',
            '{{searchstring}}',
            '{{cmid}}');

        $replacements = array(
            new moodle_url('/mod/assessment/view.php'),
            $searchword,
            get_string('search', 'mod_assessment'),
            $this->assessment->cm->id
            );

        $content = str_replace($values, $replacements, $template);
        return $content;
    }

    public function user_listing($search) {
        $students = $this->assessment->student_list($search);
        $awards = $this->assessment->awards;

        $content = '';
        $content .= $this->user_heading($awards);

        if (!empty($students)) {
            foreach ($students as $student) {
                $content .= $this->user_row($student);
            }
        }
        return $content;
    }

    public function user_heading($awards) {
        $content = '';
        $template = '
                <div class="row userrow heading">
                    <div class="usercell userpicture">
                        <div class="userimage"></div>
                    </div>
                    <div class="usercell username">
                        <strong>{{username}}</strong>
                    </div>
                    <div class="awards awards{{awardsnum}} usercell">
                        {{awards}}
                    </div>
                    <div class="usercell feedbackheading">
                        {{feedbackshort}}
                    </div>
                    <div class="usercell actions">
                        <strong>{{feedback}}</strong>
                    </div>
                </div>';

        $values = array(
            '{{username}}',
            '{{awardsnum}}',
            '{{awards}}',
            '{{feedbackshort}}',
            '{{feedback}}');

        $replacements = array(
            get_string('username', 'mod_assessment'),
            count($awards),
            $this->user_heading_awards($awards),
            get_string('feedback', 'mod_assessment'),
            get_string('actions', 'mod_assessment')
            );

        $content .= str_replace($values, $replacements, $template);
        return $content;
    }

    public function user_heading_awards($awards) {
        $content = '';
        $template = '<div id="{{name}}" class="awardheader inactive"><strong>{{name}}</strong></div>';

        foreach ($awards as $award) {
            $content .= str_replace('{{name}}', $award->awardname, $template);
        }
        return $content;
    }

    public function user_row($user, $canassess = true) {
        global $OUTPUT, $COURSE;
        $content = '';
        $template = '
        <div data-id="{{userid}}" data-assessment={{assessmentid}} class="row userrow">
            <div id="feedback{{userid}}" class="usercell userpicture linktouser">
                <div class="userimage">
                    {{userimage}}
                </div>
            </div>
            <div class="usercell username linktouser">
                {{username}}
            </div>
            <div id="awards" class="awards awards{{awardsnum}} usercell">
                {{awards}}
            </div>
            <div class="usercell feedbackcontent">
                {{feedback}}
            </div>
            <div class="usercell actions">
                <a href="{{feedbacklink}}" data-id="{{userid}}" class="{{feedbackclass}}">
                    {{addfeedback}}
                    {{loading}}
                    {{check}}
                    {{printlink}}
                </a>
            </div>
            <div class="clearfix"></div>
            {{feedbackrow}}
        </div>';

        $values = array(
            '{{userid}}',
            '{{assessmentid}}',
            '{{userimage}}',
            '{{username}}',
            '{{awardsnum}}',
            '{{awards}}',
            '{{feedback}}',
            '{{feedbacklink}}',
            '{{feedbackclass}}',
            '{{addfeedback}}',
            '{{loading}}',
            '{{check}}',
            '{{printlink}}',
            '{{feedbackrow}}');

        $editimgalt = get_string('addfeedback', 'mod_assessment');
        $eicon = new pix_icon('i/edit', $editimgalt, '', array('title' => $editimgalt));
        $editicon = $OUTPUT->render($eicon);

        if ($user->singleuser || $this->assessment->assessment->htmlfeedback) {
            $page = optional_param('page', 0, PARAM_INT);
            $feedbacklink = new moodle_url('/mod/assessment/user.php',
                array('assessmentid' => $this->assessment->assessment->id, 'userid' => $user->id, 'edit' => 1, 'page' => $page));
            $feedbackclass = '';
        } else {
            $feedbacklink = '#';
            $feedbackclass = 'feedbacklink';
        }

        $loaderimgalt = get_string('loading', 'mod_assessment');
        $licon = new pix_icon('i/ajaxloader', $loaderimgalt, '', array('title' => $loaderimgalt, 'class' => 'loadericon hidden'));
        $loadericon = $OUTPUT->render($licon);

        $checkimgalt = get_string('checked', 'mod_assessment');
        $cicon = new pix_icon('t/check', $checkimgalt, '', array('title' => $checkimgalt, 'class' => 'checkicon hidden'));
        $checkicon = $OUTPUT->render($cicon);

        $printimgalt = get_string('print', 'mod_assessment');
        $pricon = new pix_icon('t/print', $printimgalt, '', array('title' => $printimgalt, 'class' => 'printicon'));
        $printicon = $OUTPUT->render($pricon);
        $printuserurl = new moodle_url('/mod/assessment/print.php', array('userid' => $user->id, 'assessmentid' => $this->assessment->assessment->id));
        $printlink = html_writer::link($printuserurl, $printicon, array('class' => 'userprintlink'));

        $userurl = new moodle_url('/user/view.php',
            array('course' => $COURSE->id, 'id' => $user->id));
        $userlink = html_writer::link($userurl, fullname($user));

        if (!$this->assessment->assessment->htmlfeedback) {
            $feedbackrow = $this->js_feedback_row($user);
        } else {
            $feedbackrow = '';
        }
        if (!$canassess) {
            $feedbacklink = $editicon = $loadericon = $checkicon = '';
        }

        $replacements = array(
            $user->id,
            $user->assessmentid,
            $OUTPUT->user_picture($user),
            $userlink,
            count($user->awards),
            $this->user_row_awards($user, $canassess),
            $user->feedback->feedback,
            $feedbacklink,
            $feedbackclass,
            $editicon,
            $loadericon,
            $checkicon,
            $printlink,
            $feedbackrow
            );

        $content .= str_replace($values, $replacements, $template);
        return $content;
    }

    public function single_user_row($user, $canassess) {
        global $OUTPUT, $COURSE;
        $content = '';
        $template = '
        <div class="well">
            {{printbtn}}
            <div class="media">
              <div class="pull-left">
                  {{userimage}}
              </div>
              <div class="media-body">
                <h4 class="media-heading">{{username}}</h4>
              </div>
            </div>

            <hr>

            <div class="media">
              <div class="pull-left">
                  {{awardimage}}
                  <br>
                  <div class="text-center">{{awardname}}</div>
              </div>
              <div class="media-body">
                <h4 class="media-heading">{{strfeedback}}</h4>
                {{feedback}}
              </div>
            </div>


        </div>';

        $values = array(
            '{{printbtn}}',
            '{{userimage}}',
            '{{username}}',
            '{{awardimage}}',
            '{{awardname}}',
            '{{strfeedback}}',
            '{{feedback}}');

        $awardimage = $awardname = '';
        foreach ($user->awards as $award) {
            if ($award->active == 'inactive') {
                continue;
            }
            $awardimage = $award->awardimage;
            $awardname = $award->awardname;
        }

        $userurl = new moodle_url('/user/view.php',
            array('course' => $COURSE->id, 'id' => $user->id));
        $userlink = html_writer::link($userurl, fullname($user));

        $printurl = new moodle_url('/mod/assessment/print.php', array('assessmentid' => $this->assessment->assessment->id, 'userid' => $user->id));
        $button = new single_button($printurl, get_string('print', 'mod_assessment'));

        $printbtn = html_writer::tag('div', $OUTPUT->render($button), array('class' => 'pull-right'));

        $replacements = array(
            $printbtn,
            $OUTPUT->user_picture($user),
            $userlink,
            $awardimage,
            $awardname,
            get_string('feedback', 'mod_assessment'),
            $user->feedback->feedback
            );

        $content .= str_replace($values, $replacements, $template);
        return $content;
    }

    public function js_feedback_row($user) {
        $template = '
        <div id="feedback" class="feedbackrow {{hidden}}">
            <div class="listview commentinputbox">
                <div class="lv-item">
                    <div class="media">
                        <div id="teacherimage" class="float-left teacherimage">
                            {{teacherimage}}
                        </div>
                        <div class="commentbody">
                            <div contenteditable="true" class="commentinput">{{feedback}}</div>
                            <div id="commentactions" class="float-right">
                                <button id="commentcancel" class="commentcancel btn btn-xs btn-default">
                                    {{cancel}}
                                </button>
                                <button id="commentadd" class="commentadd btn btn-xs btn-info">
                                    {{save}}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        $values = array(
            '{{hidden}}',
            '{{teacherimage}}',
            '{{feedback}}',
            '{{cancel}}',
            '{{save}}');

        if ($this->assessment->singleuser) {
            $hidden = '';
        } else {
            $hidden = 'hidden';
        }

        $replacements = array(
            $hidden,
            $user->feedback->sender->picture,
            $user->feedback->feedback,
            get_string('cancel', 'mod_assessment'),
            get_string('save', 'mod_assessment')
            );

        $content = str_replace($values, $replacements, $template);
        return $content;
    }

    public function show_feedback() {
        if (!$this->assessment->singleuser) {
            return '';
        }

        $user = $this->assessment->singleuser;

        $template = '
        <div id="feedback" class="feedbacktext">
            <div class="listview commentinputbox">
                <div class="lv-item">
                    <div class="media">
                        <div id="teacherimage" class="pull-left teacherimage">
                            {{teacherimage}}
                        </div>
                        <div class="commentbody">
                            <div>{{feedback}}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        $values = array(
            '{{teacherimage}}',
            '{{feedback}}'
        );

        $replacements = array(
            $user->feedback->sender->picture,
            $user->feedback->feedback
        );
        $content = str_replace($values, $replacements, $template);
        return $content;
    }

    public function user_row_awards($user, $canassess) {
        $content = '';
        $template = '<div id="{{id}}" class="{{name}} award {{active}}">{{image}}</div>';

        $values = array(
            '{{id}}',
            '{{name}}',
            '{{active}}',
            '{{image}}'
            );

        foreach ($user->awards as $award) {
            $replacements = array(
                $award->id,
                $award->awardname,
                $award->active,
                $award->awardimage);

            if (!$canassess && $award->active == 'inactive') {
                continue;
            }
            $content .= str_replace($values, $replacements, $template);
        }
        return $content;
    }

    public function user_view($canassess) {
        $content = '';
        if ($this->assessment->singleuser) {
            if (!$canassess && $this->assessment->singleuser->grade == '') {
                return html_writer::tag('div', get_string('ungraded', 'mod_assessment'), array('class' => 'alert alert-warning'));
            }
            if ($canassess) {
                $content = $this->user_heading($this->assessment->singleuser->awards);
                $content .= $this->user_row($this->assessment->singleuser, $canassess);
            } else {
                $content .= $this->single_user_row($this->assessment->singleuser, $canassess);
            }
        }
        return $content;
    }

    public function pagination() {
        global $OUTPUT;
        if (!$this->assessment->pages) {
            return '';
        }
        if ($this->assessment->pages->numpages == 1) {
            return '';
        }
        $pages = $this->assessment->pages;

        $template = '
        <ul class="pagination lv-pagination">

            <li>
                <a href="{{prevurl}}">
                    {{leftarrow}}
                </a>
            </li>
            {{pages}}
            <li>
                <a href="{{nexturl}}">
                    {{rightarrow}}
                </a>
            </li>
        </ul>';

        $values = array(
            '{{prevurl}}',
            '{{leftarrow}}',
            '{{pages}}',
            '{{nexturl}}',
            '{{rightarrow}}');

        $prevurl = new moodle_url('/mod/assessment/view.php', array('id' => $this->assessment->cm->id, 'page' => $pages->prev));
        $nexturl = new moodle_url('/mod/assessment/view.php', array('id' => $this->assessment->cm->id, 'page' => $pages->next));
        $leftalt = get_string('previous', 'mod_assessment');
        $licon = new pix_icon('t/left', $leftalt, '', array('title' => $leftalt));
        $leftarrow = $OUTPUT->render($licon);

        $rightalt = get_string('next', 'mod_assessment');
        $ricon = new pix_icon('t/right', $rightalt, '', array('title' => $rightalt));
        $rightarrow = $OUTPUT->render($ricon);

        $replacements = array(
            $prevurl,
            $leftarrow,
            $this->pagination_pages($pages),
            $nexturl,
            $rightarrow);

        $content = str_replace($values, $replacements, $template);
        return $content;
    }

    public function pagination_pages($pages) {
        $content = '';
        $template = '<li class="{{active}}"><a href="{{link}}">{{number}}</a></li>';

        $values = array(
            '{{active}}',
            '{{link}}',
            '{{number}}');

        foreach ($pages->pagenums as $page) {
            $link = new moodle_url('/mod/assessment/view.php', array('id' => $this->assessment->cm->id, 'page' => $page));
            $active = '';
            if ($pages->current == $page) {
                $active = 'active';
            }

            $replacements = array(
                $active,
                $link,
                $page + 1);

            $content .= str_replace($values, $replacements, $template);
        }
        return $content;
    }

    public function return_to_userlisting($page) {
        global $CFG;

        $url = new moodle_url('/mod/assessment/view.php', array('id' => $this->assessment->cm->id, 'page' => $page));
        $button = html_writer::link($url, get_string('return', 'mod_assessment'), array('class' => 'btn btn-default'));
        return $button;
    }

    public function pdf_listing() {
        global $CFG;
        $students = $this->assessment->student_list('');
        $awards = $this->assessment->awards;

        $content = '<div class="path-mod-assessment pdf-print">';
        $content .= '<div style="text-align:center; display:inline-block; width:75%">
                        <h2 style="color:red">'.$this->assessment->assessment->name.'</h2>
                    </div>';
        $content .= '<table style="padding: 3px">';
        $content .= $this->pdf_heading($awards);
        
        if (!empty($students)) {
            foreach ($students as $student) {
                $content .= $this->pdf_row($student);
            }
        }
        $content .= '</table>';
        $content .= '</div>';
        return $content;
    }

    public function pdf_heading($awards) {
        $content = '';
        $template = '
                <tr>
                    <td style="width: 10%">
                    </td>
                    <td style="width: 20%">
                        <strong>{{username}}</strong>
                    </td>
                    <td style="width: 10%">
                        <strong>{{awards}}</strong>
                    </td>
                    <td style="width: 50%">
                        <strong>{{feedback}}</strong>
                    </td>
                </tr>';

        $values = array(
            '{{username}}',
            '{{awards}}',
            '{{feedback}}');

        $replacements = array(
            get_string('username', 'mod_assessment'),
            get_string('award', 'mod_assessment'),
            get_string('feedback', 'mod_assessment')
            );

        $content .= str_replace($values, $replacements, $template);
        return $content;
    }

    public function pdf_row($user) {
        global $OUTPUT, $COURSE;
        $content = '';
        $template = '
        <tr>
            <td style="width: 10%; vertical-align: top; border-bottom: 1px solid #CCC">
                <div class="userimage">
                    {{userimage}}
                </div>
            </td>
            <td style="width: 20%; vertical-align: top; border-bottom: 1px solid #CCC">
                <br><br>
                {{username}}
            </td>
            <td style="width: 10%; vertical-align: top; border-bottom: 1px solid #CCC; text-align: center;">
                {{awardimage}}
                {{awardname}}
            </td>
            <td style="width: 50%; vertical-align: top; border-bottom: 1px solid #CCC">
                <br><br>
                {{feedback}}
            </td>
        </tr>';

        $values = array(
            '{{userimage}}',
            '{{username}}',
            '{{awardimage}}',
            '{{awardname}}',
            '{{feedback}}');

        $userlink = fullname($user);

        $awardimage = $awardname = '';
        foreach ($user->awards as $award) {
            if ($award->active == 'inactive') {
                continue;
            }
            $awardimage = $award->awardimage;
            $awardname = $award->awardname;
        }

        $replacements = array(
            $OUTPUT->user_picture($user),
            $userlink,
            $awardimage,
            $awardname,
            $user->feedback->feedback,
            );

        $content .= str_replace($values, $replacements, $template);
        return $content;
    }

    public function pdf_user($user) {
        global $OUTPUT, $COURSE;
        $this->assessment->set_user($user);
        $user = $this->assessment->singleuser;
        $content = '';
        $template = '

            <div style="text-align:left; display:inline-block; color:black;">
                    {{userimage}}
                <br>{{username}}
                <br />{{pluginname}} {{strdate}} {{date}}
            </div>
            
            <div style="text-align:center; display:inline-block; width:75%; color:black;">
                <h2 style="color:red">{{assessmentname}}</h2>
            </div>
            <div style="text-align:center; display:inline-block; width:75%; color:black;">
                {{awardimage}}
                 <br>
                {{awardname}}
            </div>
            <div style="text-align:center;>
                <div style="text-align:left; width: 50%;>
                <h3>{{strfeedback}}</h3><br>
                {{feedback}}
                </div>
            </div>
';

        $values = array(
            '{{userimage}}',
            '{{username}}',
            '{{pluginname}}',
            '{{strdate}}',
            '{{date}}',
            '{{assessmentname}}',
            '{{awardimage}}',
            '{{awardname}}',
            '{{strfeedback}}',
            '{{feedback}}');

        $awardimage = $awardname = '';
        $date = '';
        foreach ($user->awards as $award) {
            if ($award->active == 'inactive') {
                continue;
            }
            $awardimage = $award->awardimage;
            $awardname = $award->awardname;
            $date = userdate($award->timemodified, '%d %B %Y');
        }

        $userurl = new moodle_url('/user/view.php',
            array('course' => $COURSE->id, 'id' => $user->id));
        $userlink = fullname($user);

        $printurl = new moodle_url('/mod/assessment/print.php', array('assessmentid' => $this->assessment->assessment->id, 'userid' => $user->id));
        $button = new single_button($printurl, get_string('print', 'mod_assessment'));

        $replacements = array(
            $OUTPUT->user_picture($user, array('size' => '50px')),
            $userlink,
            get_string('pluginname', 'mod_assessment'),
            get_string('date'),
            $date,
            $this->assessment->assessment->name,
            $awardimage,
            $awardname,
            get_string('feedback', 'mod_assessment'),
            $user->feedback->feedback
            );

        $content .= str_replace($values, $replacements, $template);
        return $content;
    }



}