<div >
    <h3>Create a new PTP job <a class="btn btn-default btn-xs pull-right" href="{{route('manual-tutorials', ['ptp', 'about'])}}" title="More information on MAIA" target="_blank"><i class="fas fa-info-circle"></i></a></h3>
    <p>
       Insgesamte anzahl der punkte: {{ $pointCount }}
    </p>


    <form > <!--- submit an ptp api dann  später-->
        <fieldset>
            <legend>konvertierungs sam</legend>
            <div>
                 INFO TEXT, Fragen ob nach Labels selektiert werden soll. STANDARD: ALLE
            </div>
           {{-- <div class="form-group">
                <div class="radio">
                    <label for="point_to_polygon_sam">
                        <input type="radio" id="point_to_polygon_sam" name="conversion_method" value="sam" >
                        <strong>Point to polygon pics</strong>--}}
                        <div class="help-block">
                           irgendwas als hilfe. <a href="{{route('manual-tutorials', ['sam', 'novelty-detection'])}}">More information.</a>
                        </div>
                   {{-- </label>
                </div>
            </div>--}}
        </fieldset>

        <fieldset >


            <div id="ptp-label-selector"></div>

            <div class="form-group">
                <label for="nd_clusters">Min Max Threshold</label>
                <select class ="form-control">
                    <option> 1 </option>
                    <option> 2 </option>
                </select>
            </div>

            <div class="form-group">
                <label for="nd_clusters">Ignore converted points</label>
                <select class ="form-control">
                    <option> yes </option>
                    <option> n </option>
                </select>
            </div>

            <div class="form-group">
                <label for="nd_clusters">Nrfergg</label>
                <select class ="form-control">
                    <option> Option1 </option>
                    <option> Option2 </option>
                    <option> Option3 </option>
                </select>
            </div>


        </fieldset>



        <div id="ptp-job-form">


        </div>








     {{--   @if (!$user->can('sudo'))
            <button type="submit" class="btn btn-success pull-right" disabled>Create job</button>
        @else
            <button type="submit" class="btn btn-success pull-right" :disabled="canSubmit || null">Create job</button>
        @endif
--}}

    </form>


</div>

@push('scripts')
    <script type="module">


    </script>
@endpush


