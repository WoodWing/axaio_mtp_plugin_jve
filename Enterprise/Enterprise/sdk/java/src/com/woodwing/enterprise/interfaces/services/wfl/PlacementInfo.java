/**
 * PlacementInfo.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.wfl;

public class PlacementInfo  implements java.io.Serializable {
    private java.lang.String id;

    private double left;

    private double top;

    private double width;

    private double height;

    public PlacementInfo() {
    }

    public PlacementInfo(
           java.lang.String id,
           double left,
           double top,
           double width,
           double height) {
           this.id = id;
           this.left = left;
           this.top = top;
           this.width = width;
           this.height = height;
    }


    /**
     * Gets the id value for this PlacementInfo.
     * 
     * @return id
     */
    public java.lang.String getId() {
        return id;
    }


    /**
     * Sets the id value for this PlacementInfo.
     * 
     * @param id
     */
    public void setId(java.lang.String id) {
        this.id = id;
    }


    /**
     * Gets the left value for this PlacementInfo.
     * 
     * @return left
     */
    public double getLeft() {
        return left;
    }


    /**
     * Sets the left value for this PlacementInfo.
     * 
     * @param left
     */
    public void setLeft(double left) {
        this.left = left;
    }


    /**
     * Gets the top value for this PlacementInfo.
     * 
     * @return top
     */
    public double getTop() {
        return top;
    }


    /**
     * Sets the top value for this PlacementInfo.
     * 
     * @param top
     */
    public void setTop(double top) {
        this.top = top;
    }


    /**
     * Gets the width value for this PlacementInfo.
     * 
     * @return width
     */
    public double getWidth() {
        return width;
    }


    /**
     * Sets the width value for this PlacementInfo.
     * 
     * @param width
     */
    public void setWidth(double width) {
        this.width = width;
    }


    /**
     * Gets the height value for this PlacementInfo.
     * 
     * @return height
     */
    public double getHeight() {
        return height;
    }


    /**
     * Sets the height value for this PlacementInfo.
     * 
     * @param height
     */
    public void setHeight(double height) {
        this.height = height;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof PlacementInfo)) return false;
        PlacementInfo other = (PlacementInfo) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.id==null && other.getId()==null) || 
             (this.id!=null &&
              this.id.equals(other.getId()))) &&
            this.left == other.getLeft() &&
            this.top == other.getTop() &&
            this.width == other.getWidth() &&
            this.height == other.getHeight();
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getId() != null) {
            _hashCode += getId().hashCode();
        }
        _hashCode += new Double(getLeft()).hashCode();
        _hashCode += new Double(getTop()).hashCode();
        _hashCode += new Double(getWidth()).hashCode();
        _hashCode += new Double(getHeight()).hashCode();
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(PlacementInfo.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:SmartConnection", "PlacementInfo"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("id");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Id"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "string"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("left");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Left"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("top");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Top"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("width");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Width"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("height");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Height"));
        elemField.setXmlType(new javax.xml.namespace.QName("http://www.w3.org/2001/XMLSchema", "double"));
        elemField.setNillable(false);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
