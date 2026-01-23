import { Controller } from "@hotwired/stimulus"

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ["minSlider", "maxSlider", "resetButton", "filterInfo", "rockItem", "minValue", "maxValue"]

    connect() {
        this.initializeGradeMapping()
        this.updateFilter()
        this.updateValueDisplay()
    }

    updateFilter() {
        const minGrade = parseInt(this.minSliderTarget.value)
        const maxGrade = parseInt(this.maxSliderTarget.value)
        
        // Ensure min is not greater than max
        if (minGrade > maxGrade) {
            this.minSliderTarget.value = maxGrade
            return
        }

        let visibleCount = 0
        let totalCount = this.rockItemTargets.length

        console.log(`Filtering: Grade ${minGrade}-${maxGrade}`)

        this.rockItemTargets.forEach((rock, index) => {
            // Get the route grades from the data attribute
            const routeGradesString = rock.dataset.routeGrades || ''
            const routeGrades = routeGradesString ? routeGradesString.split(',').map(grade => parseInt(grade.trim())).filter(grade => !isNaN(grade)) : []
            
            console.log(`Rock ${index}: Route grades: [${routeGrades.join(', ')}]`)
            
            // Check if any route grade falls within the selected range
            let hasRoutesInRange = false
            
            // Convert grade range to numerical ranges
            const minRange = this.getMinRangeForGrade(minGrade)
            const maxRange = this.getMaxRangeForGrade(maxGrade)
            
            console.log(`  -> Checking range ${minRange}-${maxRange}`)
            console.log(`  -> Grade ${minGrade} maps to min ${minRange}, Grade ${maxGrade} maps to max ${maxRange}`)
            
            for (const grade of routeGrades) {
                if (grade >= minRange && grade <= maxRange) {
                    hasRoutesInRange = true
                    console.log(`  -> Grade ${grade} matches range ${minRange}-${maxRange}`)
                    break
                }
            }
            
            console.log(`  -> Has routes in range: ${hasRoutesInRange}`)
            
            if (hasRoutesInRange) {
                rock.style.display = ''
                visibleCount++
            } else {
                rock.style.display = 'none'
            }
        })

        // Update info text
        this.filterInfoTarget.textContent = `${visibleCount} von ${totalCount} Felsen angezeigt`
        console.log(`Result: ${visibleCount} of ${totalCount} rocks visible`)
    }

    getMinRangeForGrade(grade) {
        const ranges = {
            1: 1, 2: 2, 3: 5, 4: 8, 5: 11, 6: 16, 7: 21, 8: 28, 9: 36, 10: 44, 11: 52
        }
        return ranges[grade] || 1
    }

    getMaxRangeForGrade(grade) {
        const ranges = {
            1: 1, 2: 4, 3: 7, 4: 10, 5: 15, 6: 20, 7: 27, 8: 35, 9: 43, 10: 51, 11: 57
        }
        return ranges[grade] || 57
    }

    updateValueDisplay() {
        if (this.hasMinValueTarget) {
            this.minValueTarget.textContent = this.minSliderTarget.value
        }
        if (this.hasMaxValueTarget) {
            this.maxValueTarget.textContent = this.maxSliderTarget.value
        }
    }

    minSliderChanged() {
        // Ensure min doesn't exceed max
        if (parseInt(this.minSliderTarget.value) > parseInt(this.maxSliderTarget.value)) {
            this.minSliderTarget.value = this.maxSliderTarget.value
        }
        this.updateValueDisplay()
        this.updateFilter()
    }

    maxSliderChanged() {
        // Ensure max doesn't go below min
        if (parseInt(this.maxSliderTarget.value) < parseInt(this.minSliderTarget.value)) {
            this.maxSliderTarget.value = this.minSliderTarget.value
        }
        this.updateValueDisplay()
        this.updateFilter()
    }

    resetFilter() {
        this.minSliderTarget.value = 1
        this.maxSliderTarget.value = 11
        this.updateValueDisplay()
        this.updateFilter()
    }
}