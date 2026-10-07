package prac;



import java.util.List;

import org.openqa.selenium.By;
import org.openqa.selenium.WebDriver;
import org.openqa.selenium.WebElement;
import org.openqa.selenium.edge.EdgeDriver;



public class WebPageObjectCounter
{
	public static void main(String[] args) {

      
        System.setProperty("webdriver.msedge.driver", "D:\\STQA TYCS\\practical\\msedgedriver.exe");

       
        WebDriver driver = new EdgeDriver();

        driver.get("https://www.google.com");

        driver.manage().window().maximize();

        List<WebElement> links = driver.findElements(By.tagName("a"));
        int linkCount = links.size();

        List<WebElement> images = driver.findElements(By.tagName("img"));
        int imageCount = images.size();

        List<WebElement> buttons = driver.findElements(By.tagName("button"));
        int buttonCount = buttons.size();

        List<WebElement> inputs = driver.findElements(By.tagName("input"));
        int inputCount = inputs.size();

        int totalObjects = linkCount + imageCount + buttonCount + inputCount;

        System.out.println("===== Web Page Object Count =====");
        System.out.println("Total Links       : " + linkCount);
        System.out.println("Total Images      : " + imageCount);
        System.out.println("Total Buttons     : " + buttonCount);
        System.out.println("Total Input Fields: " + inputCount);
        System.out.println("---------------------------------");
        System.out.println("Total Objects     : " + totalObjects);

        
        driver.quit();
    }
}

