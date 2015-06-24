{{--
  Displays a nested list of comments

  params:
  commentable: a Commentable sub-class.

--}}

<div class="comments">
  @foreach($commentable->comments->sortByDesc('score') as $comment)
    <div class="comment">
      <div class="comment-vote">
        <!-- this is copy-pasted, TODO: adapt voting logic -->
        <div>
          <a href="#" data-vote-btn="up:comment:{{{ $comment->id }}}"
            class="vote-btn upvote {{{ ($comment->hasUpVote(Session::get('student_id'))) ? 'voted' : '' }}}"
            ><i class="fa fa-arrow-up"></i
          ></a>
        </div>
        <div>
          <a href="#" data-vote-btn="down:comment:{{{ $comment->id }}}"
            class="vote-btn downvote {{{ ($comment->hasDownVote(Session::get('student_id'))) ? 'voted' : '' }}}"
            ><i class="fa fa-arrow-down"></i
          ></a>
        </div>
      </div>
      <div class="comment-header">
        <a href="{{{ $comment->student->pageURL }}}" target="_blank">{{{ $comment->student->fullname }}}</a>
        &ndash; <span data-vote-score="comment:{{{ $comment->id }}}">{{{ $comment->score }}}</span> points,
        {{{ $comment->created_at }}}
        @if($comment->student->id == StudentInfo::getId())
          &ndash; <a href="#">modifier</a>
        @endif
        &ndash; <a href="#" data-comment-action="comment:{{{ $comment->id }}}">reply</a>
      </div>
      <div class="comment-body">{{{ $comment->body }}}</div>
      <div data-comment-form="comment:{{{ $comment->id }}}" class="hidden">
        @include('forms.comment', ['target_comment' => $comment->id ])
      </div>
      @if (count($comment->comments) > 0)
        @include('components.comments_thread', ["commentable" => $comment])
      @endif
    </div>
  @endforeach
</div>